<?php

namespace Concrete\Package\CommunityStoreBccPayway\Src\CommunityStore\Payment\Methods\BccPayway;

use Concrete\Core\Config\Repository\Repository;
use Concrete\Core\Error\UserMessageException;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Support\Facade\Application;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Core\Utility\Service\Identifier;
use Concrete\Package\CommunityStore\Src\CommunityStore;
use Concrete\Package\CommunityStoreBccPayway;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use MLocati\PayWay;
use RuntimeException;
use Throwable;

defined('C5_EXECUTE') or die('Access Denied');

class BccPaywayPaymentMethod extends CommunityStore\Payment\Method
{
    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::getName()
     */
    public function getName()
    {
        return t('Checkout with BCC PayWay');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::getPaymentMinimum()
     */
    public function getPaymentMinimum()
    {
        return 0.01;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::isExternal()
     */
    public function isExternal()
    {
        return true;
    }

    public function dashboardForm()
    {
        $app = Application::getFacadeApplication();
        $config = $app->make(Repository::class);
        $this->set('form', $app->make('helper/form'));
        $this->set('creditCardImages', $app->make(CommunityStoreBccPayway\CreditCardImages::class));
        $this->set('environment', (string) $config->get('community_store_bcc_payway::options.environment'));
        $environments = [
            'sandbox' => t('Test'),
            'production' => t('Production'),
        ];
        $servicesURLs = [];
        $terminalIDs = [];
        $signatureKeys = [];
        foreach (array_keys($environments) as $environment) {
            $servicesURLs[$environment] = (string) $config->get("community_store_bcc_payway::options.environments.{$environment}.servicesURL");
            $terminalIDs[$environment] = (string) $config->get("community_store_bcc_payway::options.environments.{$environment}.terminalID");
            $signatureKeys[$environment] = (string) $config->get("community_store_bcc_payway::options.environments.{$environment}.signatureKey");
        }
        $this->set('environments', $environments);
        $this->set('servicesURLs', $servicesURLs);
        $this->set('terminalIDs', $terminalIDs);
        $this->set('signatureKeys', $signatureKeys);
    }

    /**
     * @param array|mixed $args
     * @param \Concrete\Core\Error\ErrorList\ErrorList $e
     *
     * @return \Concrete\Core\Error\ErrorList\ErrorList
     */
    public function validate($args, $e)
    {
        $environments = [
            'sandbox' => t('Test'),
            'production' => t('Production'),
        ];
        $args = (is_array($args) ? $args : []) + [
            'paymentMethodHandle' => null,
            'paymentMethodEnabled' => null,
            'environment' => null,
        ];
        $myIndex = is_array($args['paymentMethodHandle']) ? array_search(CommunityStoreBccPayway\Controller::PAYMENTMETHOD_HANDLE, $args['paymentMethodHandle'], true) : false;
        $isEnabled = $myIndex !== false && is_array($args['paymentMethodEnabled']) && !empty($args['paymentMethodEnabled'][$myIndex]);
        if (!$isEnabled) {
            return $e;
        }
        $environment = $args['bccPaywayEnvironment'];
        if (!is_string($environment) || !isset($environments[$environment])) {
            $e->add(t('Please specify which environment should be used for BCC PayWay', 'bccPaywayEnvironment'));
        }
        foreach ($environments as $environmentKey => $environmentName) {
            if ($environmentKey !== $environment) {
                continue;
            }
            $args += [
                'bccPaywayServicesURL_' . $environmentKey => null,
                'bccPaywayTerminalID_' . $environmentKey => null,
                'bccPaywaySignatureKey_' . $environmentKey => null,
            ];
            $servicesURL = is_string($args['bccPaywayServicesURL_' . $environmentKey]) ? trim($args['bccPaywayServicesURL_' . $environmentKey]) : '';
            if ($servicesURL === '') {
                $e->add(t('Please specify the URL of the bank services for the %s environment of BCC PayWay', $environmentName), 'bccPaywayServicesURL_' . $environmentKey);
            } elseif (PayWay\Client::normalizeServicesUrl($servicesURL) === '') {
                $e->add(t('The URL of the bank services for the %s environment of BCC PayWay is wrong', $environmentName), 'bccPaywayServicesURL_' . $environmentKey);
            }
            $terminalID = is_string($args['bccPaywayTerminalID_' . $environmentKey]) ? trim($args['bccPaywayTerminalID_' . $environmentKey]) : '';
            if ($terminalID === '') {
                $e->add(t('Please specify the terminal ID for the %s environment of BCC PayWay', $environmentName), 'bccPaywayTerminalID_' . $environmentKey);
            }
            $signatureKey = is_string($args['bccPaywaySignatureKey_' . $environmentKey]) ? trim($args['bccPaywaySignatureKey_' . $environmentKey]) : '';
            if ($signatureKey === '') {
                $e->add(t('Please specify the signature key for the %s environment of BCC PayWay', $environmentName), 'bccPaywaySignatureKey_' . $environmentKey);
            }
        }

        return $e;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::save()
     */
    public function save(array $data = [])
    {
        $app = Application::getFacadeApplication();
        $config = $app->make(Repository::class);
        $config->save('community_store_bcc_payway::options.environment', isset($data['bccPaywayEnvironment']) && is_string($data['bccPaywayEnvironment']) ? trim($data['bccPaywayEnvironment']) : '');
        foreach (['sandbox', 'production'] as $environment) {
            $config->save("community_store_bcc_payway::options.environments.{$environment}.servicesURL", isset($data['bccPaywayServicesURL_' . $environment]) && is_string($data['bccPaywayServicesURL_' . $environment]) ? trim($data['bccPaywayServicesURL_' . $environment]) : '');
            $config->save("community_store_bcc_payway::options.environments.{$environment}.terminalID", isset($data['bccPaywayTerminalID_' . $environment]) && is_string($data['bccPaywayTerminalID_' . $environment]) ? trim($data['bccPaywayTerminalID_' . $environment]) : '');
            $config->save("community_store_bcc_payway::options.environments.{$environment}.signatureKey", isset($data['bccPaywaySignatureKey_' . $environment]) && is_string($data['bccPaywaySignatureKey_' . $environment]) ? trim($data['bccPaywaySignatureKey_' . $environment]) : '');
        }
        $bccPaywayCreditCardImages = isset($data['bccPaywayCreditCardImages']) ? $data['bccPaywayCreditCardImages'] : null;
        if (is_array($bccPaywayCreditCardImages)) {
            $app->make(CommunityStoreBccPayway\CreditCardImages::class)->setWantedImageHandles($bccPaywayCreditCardImages, true);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::checkoutForm()
     */
    public function checkoutForm()
    {
        $app = Application::getFacadeApplication();
        $this->set('creditCardImages', $app->make(CommunityStoreBccPayway\CreditCardImages::class));
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::isExternalActionGET()
     */
    public function isExternalActionGET()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\Method::getAction()
     */
    public function getAction()
    {
        $app = Application::getFacadeApplication();
        $em = $app->make(EntityManagerInterface::class);
        $config = $app->make(Repository::class);
        $urlResolver = $app->make(ResolverManagerInterface::class);
        $client = $app->make(CommunityStoreBccPayway\PayWayClient\Factory::class)->buildClient();
        $session = $app->make('session');
        $siteName = tc('SiteName', $app->make('site')->getSite()->getSiteName());
        $currencyCode = $config->get('community_store.currency');
        if (!PayWay\Dictionary\Currency::isCodeValid($currencyCode)) {
            throw new RuntimeException(t('The currency is not configured, or it has a wrong value'));
        }
        $orderID = (int) $session->get('orderID');
        $shopID = $orderID . chr(round(mt_rand(ord('a'), ord('f')))) . $app->make(Identifier::class)->getString(40);
        $order = CommunityStore\Order\Order::getByID($orderID);
        $initLog = new CommunityStoreBccPayway\Entity\InitLog($client->getEnvironment(), $order, $shopID);
        if ($initLog->getEnvironment() === '') {
            throw new RuntimeException(t('The environment is not configured'));
        }
        $initResponse = null;
        try {
            $customer = new CommunityStore\Customer\Customer();
            $initRequest = new PayWay\Init\Request();
            $initRequest
                ->setTID($client->getTerminalID())
                ->setShopID($shopID)
                ->setShopUserRef($customer->getEmail())
                ->setTrType(PayWay\Dictionary\TrType::CODE_PURCHASE)
                ->setAmountAsFloat($order->getTotal())
                ->setCurrencyCode($currencyCode)
                ->setLangID(strtoupper(Localization::activeLanguage()))
                ->setNotifyURL((string) $urlResolver->resolve([CommunityStoreBccPayway\Controller::PATH_CALLBACK_STANDARD]))
                ->setErrorURL((string) $urlResolver->resolve([CommunityStoreBccPayway\Controller::PATH_CALLBACK_ERROR]))
                ->setCallbackURL((string) $urlResolver->resolve([CommunityStoreBccPayway\Controller::PATH_CALLBACK_SERVER2SERVER]))
                ->setDescription(t('Order %1$s on %2$s', $orderID, $siteName))
            ;
            $initLog->setRequestJson(json_encode($initRequest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $client->addListener(static function (PayWay\Http\Event $event) use ($initLog) {
                $initLog
                    ->setRequestUrl($event->getUrl())
                    ->setRequestXml($event->getRequestBody())
                    ->setRawResponse($event->getResponse()->getBody())
                ;
            });
            $initResponse = $client->init($initRequest);
            $initLog->setResponseJson(json_encode($initResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            $exception = null;
        } catch (Exception $x) {
            $exception = $x;
        } catch (Throwable $x) {
            $exception = $x;
        } finally {
            if ($exception !== null) {
                if ($exception instanceof \MLocati\PayWay\Exception) {
                    $initLog->setException($exception->getMessage());
                } else {
                    $initLog->setException((string) $exception);
                }
            }
            if ($initResponse !== null) {
                $initLog->setPaymentID($initResponse->getPaymentID());
            }
            $em->persist($initLog);
            $em->flush();
        }
        if ($exception !== null) {
            throw $exception;
        }
        if ($initResponse->getRc() !== PayWay\Dictionary\RC::TRANSACTION_OK || $initResponse->isError() || $initResponse->getRedirectURL() === '') {
            throw new UserMessageException(t('Transaction initialization failed (error code: %s)', $initResponse->getRc()));
        }
        $session->set('storeBccPayWayShopID', $shopID);

        return $initResponse->getRedirectURL();
    }
}
