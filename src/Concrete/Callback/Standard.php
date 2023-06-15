<?php

namespace Concrete\Package\CommunityStoreBccPayway\Callback;

use Concrete\Core\Application\Service\UserInterface;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Url\Resolver\Manager\ResolverManagerInterface;
use Concrete\Package\CommunityStoreBccPayway;
use Doctrine\ORM\EntityManagerInterface;
use MLocati\PayWay;

defined('C5_EXECUTE') or die('Access Denied');

class Standard
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

    public function __construct(Request $request, EntityManagerInterface $entityManager, ResolverManagerInterface $urlResolver, ResponseFactoryInterface $responseFactory, UserInterface $userInterface)
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
        $this->urlResolver = $urlResolver;
        $this->responseFactory = $responseFactory;
        $this->userInterface = $userInterface;
    }

    public function __invoke()
    {
        $qsData = $this->request->query->all();
        $shopID = isset($qsData['id']) ? $qsData['id'] : '';
        if (is_string($shopID) && $shopID !== '') {
            $init = $this->entityManager->getRepository(CommunityStoreBccPayway\Entity\InitLog::class)->findOneBy(['shopID' => $shopID]);
        } else {
            $init = null;
        }
        if ($init === null) {
            return $this->buildRedirect('/checkout');
        }
        /** @var CommunityStoreBccPayway\Entity\InitLog $init */
        $verify = $init->getVerifyLogs()->last();
        if ($verify === null || $verify === false) {
            return $this->buildRedirect('/checkout');
        }
        /** @var CommunityStoreBccPayway\Entity\VerifyLog $verify */
        switch ($verify->getRC()) {
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
                '<small>' . t('Error code: %s', h($verify->getRC())) . '</small>',
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
