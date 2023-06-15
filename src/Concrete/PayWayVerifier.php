<?php

namespace Concrete\Package\CommunityStoreBccPayway;

use Concrete\Core\Application\Application;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Session\SessionValidator;
use Concrete\Core\System\Mutex\MutexBusyException;
use Concrete\Core\System\Mutex\MutexInterface;
use Concrete\Package\CommunityStore\Src\CommunityStore;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MLocati\PayWay;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied');

class PayWayVerifier
{
    const MUTEX_KEY = 'cstore_bccpayway_verify';

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $app;

    /**
     * @var \Concrete\Core\System\Mutex\MutexInterface
     */
    protected $mutex;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var \Concrete\Core\Session\SessionValidator
     */
    protected $sessionValidator;

    /**
     * @var \Concrete\Package\CommunityStoreBccPayway\PayWayClient\Factory
     */
    protected $payWayClientFactory;

    public function __construct(Application $app, MutexInterface $mutex, EntityManagerInterface $entityManager, SessionValidator $sessionValidator, PayWayClient\Factory $payWayClientFactory)
    {
        $this->app = $app;
        $this->mutex = $mutex;
        $this->entityManager = $entityManager;
        $this->sessionValidator = $sessionValidator;
        $this->payWayClientFactory = $payWayClientFactory;
    }

    /**
     * @return bool
     */
    public function verifyFromCallbackURL(PayWay\Server2Server\RequestData $receivedData)
    {
        try {
            $initLog = $this->resolveInitLogForServer2Server($receivedData);
        } catch (UserMessageException $x) {
            $verifyLog = new Entity\VerifyLog('callbackURL', json_encode($receivedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $verifyLog->setException($x->getMessage());
            $this->entityManager->persist($verifyLog);
            $this->entityManager->flush();

            return false;
        }
        $verifyLog = $this->verify($initLog, 'callbackURL', $receivedData->jsonSerialize());

        return $verifyLog->getException() === '';
    }

    /**
     * @return \Concrete\Package\CommunityStoreBccPayway\Entity\VerifyLog|null
     */
    public function verifyFromNotifyURL()
    {
        if (!$this->sessionValidator->hasActiveSession()) {
            return null;
        }
        $session = $this->app->make('session');
        $shopID = $session->get('storeBccPayWayShopID');
        if (!is_string($shopID) || $shopID === '') {
            return null;
        }
        $inputData = ['shopID' => $shopID];
        $initLog = $this->entityManager->getRepository(Entity\InitLog::class)->findOneBy(['shopID' => $shopID]);
        if ($initLog === null) {
            $verifyLog = new Entity\VerifyLog('notifyURL', json_encode($inputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $verifyLog->setException(t("'%s' is not a valid/known shopID", $shopID));
            $this->entityManager->persist($verifyLog);
            $this->entityManager->flush();

            return null;
        }

        return $this->verify($initLog, 'notifyURL', $inputData);
    }

    /**
     * @throws \Concrete\Core\Error\UserMessageException
     *
     * @return \Concrete\Package\CommunityStoreBccPayway\Entity\InitLog
     */
    protected function resolveInitLogForServer2Server(PayWay\Server2Server\RequestData $receivedData)
    {
        if ($receivedData->getShopID() === '') {
            throw new UserMessageException(t('Missing parameter: %s', 'shopID'));
        }
        if ($receivedData->getPaymentID() === '') {
            throw new UserMessageException(t('Missing parameter: %s', 'paymentID'));
        }
        $initLog = $this->entityManager->getRepository(Entity\InitLog::class)->findOneBy(['shopID' => $receivedData->getShopID()]);
        if ($initLog === null) {
            throw new UserMessageException(t("'%s' is not a valid/known shopID", $receivedData->getShopID()));
        }
        if ($initLog->getPaymentID() !== $receivedData->getPaymentID()) {
            throw new UserMessageException(t(
                "The shopID '%1\$s' should be associated to the '%2\$s' paymentID, but we received '%3\$s'",
                $receivedData->getShopID(),
                $initLog->getPaymentID(),
                $receivedData->getPaymentID()
            ));
        }

        return $initLog;
    }

    protected function acquireMutex($maxSeconds = 7)
    {
        $startTime = time();
        for (;;) {
            try {
                $this->mutex->acquire(static::MUTEX_KEY);

                return;
            } catch (MutexBusyException $x) {
                $elapsedTime = time() - $startTime;
                if ($elapsedTime > $maxSeconds) {
                    throw $x;
                }
                usleep(100000); // 0.1 seconds
            }
        }
    }

    /**
     * @param string $place
     * @param array $inputData
     *
     * @return \Concrete\Package\CommunityStoreBccPayway\Entity\VerifyLog
     */
    protected function verify(Entity\InitLog $initLog, $place, array $inputData)
    {
        $this->acquireMutex();
        try {
            $verifyLog = $initLog->getVerifyLogs()->last();
            if ($verifyLog instanceof Entity\VerifyLog && $verifyLog->getException() === '' && $verifyLog->getRC() !== '') {
                return $verifyLog;
            }
            $verifyLog = new Entity\VerifyLog($place, json_encode($inputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $exception = null;
            try {
                $verifyLog->setInitLog($initLog);
                $payWayClient = $this->payWayClientFactory->buildClient($initLog->getEnvironment());
                $request = $this->createVerifyRequest($payWayClient, $initLog);
                $verifyLog->setRequestJson(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                $payWayClient->addListener(static function (PayWay\Http\Event $event) use ($verifyLog) {
                    $verifyLog
                        ->setRequestUrl($event->getUrl())
                        ->setRequestXml($event->getRequestBody())
                        ->setRawResponse($event->getResponse()->getBody())
                    ;
                });
                $response = $payWayClient->verify($request);
                $verifyLog->setResponseJson(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                if ($response->getRc() === PayWay\Dictionary\RC::TRANSACTION_OK) {
                    $order = $initLog->getAssociatedOrder();
                    $order->completeOrder($response->getPaymentID());
                    $order->updateStatus(CommunityStore\Order\OrderStatus\OrderStatus::getStartingStatus()->getHandle());
                }
                $verifyLog->setRC($response->getRc());
            } catch (Exception $x) {
                $exception = $x;
            } catch (Throwable $x) {
                $exception = $x;
            }
            if ($exception !== null) {
                if ($exception instanceof PayWay\Exception) {
                    $verifyLog->setException($exception->getMessage());
                } else {
                    $verifyLog->setException((string) $exception);
                }
            }
            $this->entityManager->persist($verifyLog);
            $this->entityManager->flush();
        } finally {
            $this->mutex->release(static::MUTEX_KEY);
        }

        return $verifyLog;
    }

    /**
     * @return \MLocati\PayWay\Verify\Request
     */
    protected function createVerifyRequest(PayWayClient $payWayClient, Entity\InitLog $initLog)
    {
        $request = new PayWay\Verify\Request();

        return $request
            ->setTID($payWayClient->getTerminalID())
            ->setShopID($initLog->getShopID())
            ->setPaymentID($initLog->getPaymentID())
        ;
    }
}
