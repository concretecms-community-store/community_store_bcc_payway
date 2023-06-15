<?php

namespace Concrete\Package\CommunityStoreBccPayway\Callback;

use Concrete\Core\Application\Service\UserInterface;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\CommunityStoreBccPayway;
use MLocati\PayWay;

defined('C5_EXECUTE') or die('Access Denied');

class Standard
{
    /**
     * @var \Concrete\Package\CommunityStoreBccPayway\PayWayVerifier
     */
    protected $payWayVerifier;

    /**
     * @var \Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface
     */
    protected $urlResolver;

    /**
     * @var \Concrete\Core\Http\ResponseFactoryInterface
     */
    protected $responseFactory;

    /**
     * @var \Concrete\Core\Application\Service\UserInterface
     */
    protected $userInterface;

    public function __construct(CommunityStoreBccPayway\PayWayVerifier $payWayVerifier, ResolverManagerInterface $urlResolver, ResponseFactoryInterface $responseFactory, UserInterface $userInterface)
    {
        $this->payWayVerifier = $payWayVerifier;
        $this->urlResolver = $urlResolver;
        $this->responseFactory = $responseFactory;
        $this->userInterface = $userInterface;
    }

    public function __invoke()
    {
        $verifyLog = $this->payWayVerifier->verifyFromNotifyURL();
        if ($verifyLog === null) {
            return $this->buildRedirect('/checkout');
        }
        switch ($verifyLog->getRC()) {
            case PayWay\Dictionary\RC::TRANSACTION_OK:
                return $this->buildRedirect('/checkout/complete');
            case PayWay\Dictionary\RC::TRANSACTION_CANCELED_BY_USER:
                return $this->buildRedirect('/checkout');
        }

        return $this->userInterface->buildErrorResponse(
            t('Payment failed'),
            implode('<br />', [
                t('We are sorry: the payment did NOT complete successfully.'),
                '',
                '<a href="' . h((string) $this->urlResolver->resolve(['/checkout'])) . '">' . t('Click here to return to the checkout page.') . '</a>',
                '',
                '',
                '<small>' . t('Error code: %s', h($verifyLog->getRC())) . '</small>',
            ])
        );
    }

    /**
     * @param string $relativePath
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function buildRedirect($relativePath)
    {
        $url = $this->urlResolver->resolve([$relativePath]);

        return $this->responseFactory->redirect((string) $url, Response::HTTP_MOVED_PERMANENTLY);
    }
}
