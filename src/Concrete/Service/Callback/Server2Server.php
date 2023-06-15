<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service\Callback;

use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Package\CommunityStore\Src\CommunityStore;
use Concrete\Package\CommunityStoreBccPayway;
use Concrete\Package\CommunityStoreBccPayway\Service\PayWayClient;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MLocati\PayWay;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied');

class Server2Server
{
    /**
     * @var \Concrete\Core\Http\Request
     */
    protected $request;

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var \Concrete\Package\CommunityStoreBccPayway\Service\PayWayClientFactory
     */
    protected $payWayClientFactory;

    /**
     * @var \Concrete\Core\Http\ResponseFactoryInterface
     */
    protected $responseFactory;

    public function __construct(Request $request, EntityManagerInterface $entityManager, CommunityStoreBccPayway\Service\PayWayClientFactory $payWayClientFactory, ResponseFactoryInterface $responseFactory)
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
        $this->payWayClientFactory = $payWayClientFactory;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke()
    {
        $receivedData = new PayWay\Server2Server\RequestData($this->request->request->all());
        $verifyLog = new CommunityStoreBccPayway\Entity\VerifyLog(json_encode($receivedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $exception = null;
        try {
            $initLog = $this->resolveInitLog($receivedData);
            $order = $initLog->getAssociatedOrder();
            $verifyLog->setInitLog($initLog);
            $payWayClient = $this->payWayClientFactory->buildClient($initLog->getEnvironment());
            $request = $this->createVerifyRequest($payWayClient, $receivedData);
            $verifyLog->setRequestJson(json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $payWayClient->addListener(static function (PayWay\Http\Event $event) use ($verifyLog) {
                $verifyLog
                    ->setRequestUrl($event->getUrl())
                    ->setRequestXml($event->getRequestBody())
                    ->setRawResponse($event->getResponse()->getBody())
                ;
            });
            $response = $payWayClient->verify($request);
            $verifyLog
                ->setResponseJson(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                ->setRC($response->getRc())
            ;
            if ($response->getRc() === PayWay\Dictionary\RC::TRANSACTION_OK) {
                $order->completeOrder($response->getPaymentID());
                $order->updateStatus(CommunityStore\Order\OrderStatus\OrderStatus::getStartingStatus()->getHandle());
            }
        } catch (Exception $x) {
            $exception = $x;
        } catch (Throwable $x) {
            $exception = $x;
        }
        if ($exception !== null) {
            if ($exception instanceof PayWay\Exception || $exception instanceof UserMessageException) {
                $verifyLog->setException($exception->getMessage());
            } else {
                $verifyLog->setException((string) $exception);
            }
        }
        $this->entityManager->persist($verifyLog);
        $this->entityManager->flush();

        return $this->responseFactory->create(
            $exception === null ? 'ok' : 'ko',
            $exception === null ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY,
            ['Content-Type' => 'text/plain']
        );
    }

    /**
     * @return \Concrete\Package\CommunityStoreBccPayway\Entity\InitLog
     */
    protected function resolveInitLog(PayWay\Server2Server\RequestData $receivedData)
    {
        if ($receivedData->getShopID() === '') {
            throw new UserMessageException(t('Missing parameter: %s', 'shopID'));
        }
        if ($receivedData->getPaymentID() === '') {
            throw new UserMessageException(t('Missing parameter: %s', 'paymentID'));
        }
        $initLog = $this->entityManager->getRepository(CommunityStoreBccPayway\Entity\InitLog::class)->findOneBy(['shopID' => $receivedData->getShopID()]);
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

    /**
     * @return \MLocati\PayWay\Verify\Request
     */
    protected function createVerifyRequest(PayWayClient $payWayClient, PayWay\Server2Server\RequestData $receivedData)
    {
        $request = new PayWay\Verify\Request();

        return $request
            ->setTID($payWayClient->getTerminalID())
            ->setShopID($receivedData->getShopID())
            ->setPaymentID($receivedData->getPaymentID())
        ;
    }
}
