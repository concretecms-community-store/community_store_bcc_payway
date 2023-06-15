<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service\Callback;

use Concrete\Core\Application\Service\UserInterface;
use Concrete\Core\Http\Request;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Package\CommunityStoreBccPayway\Entity;
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
     * @var \Concrete\Core\Application\Service\UserInterface
     */
    protected $userInterface;

    /**
     * @var \Concrete\Core\Http\ResponseFactoryInterface
     */
    protected $responseFactory;

    public function __construct(Request $request, EntityManagerInterface $entityManager, ResponseFactoryInterface $responseFactory, UserInterface $userInterface)
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
        $this->responseFactory = $responseFactory;
        $this->userInterface = $userInterface;
    }

    public function __invoke()
    {
        $qsData = $this->request->query->all();
        $shopID = isset($qsData['id']) ? $qsData['id'] : '';
        if (is_string($shopID) && $shopID !== '') {
            $init = $this->entityManager->getRepository(Entity\InitLog::class)->findOneBy(['shopID' => $shopID]);
        } else {
            $init = null;
        }
        if ($init === null) {
            return $this->responseFactory->redirect(['/checkout'], Response::HTTP_MOVED_PERMANENTLY);
        }
        /** @var Entity\InitLog $init */
        $verify = $init->getVerifyLogs()->last();
        if ($verify === null || $verify === false) {
            return $this->responseFactory->redirect(['/checkout'], Response::HTTP_MOVED_PERMANENTLY);
        }
        /** @var Entity\VerifyLog $verify */
        switch ($verify->getRC()) {
            case PayWay\Dictionary\RC::TRANSACTION_OK:
                return $this->responseFactory->redirect(['/checkout/complete'], Response::HTTP_MOVED_PERMANENTLY);
            case PayWay\Dictionary\RC::TRANSACTION_CANCELED_BY_USER:
                return $this->responseFactory->redirect(['/checkout'], Response::HTTP_MOVED_PERMANENTLY);
        }

        return $this->userInterface->buildErrorResponse(
            t('Payment failed'),
            implode('<br />', [
                t('We are sorry: the payment did NOT complete successfully.'),
                '',
                '<a href="' . h((string) $this->responseFactory->redirect(['/checkout'])) . '">' . t('Click here to return to the checkout page.') . '</a>',
                '',
                '',
                '<small>' . t('Error code: %s', h($verify->getRC())) . '</small>',
            ])
        );
    }
}
