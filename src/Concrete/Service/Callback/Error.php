<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service\Callback;

use Concrete\Core\Application\Service\UserInterface;
use Concrete\Core\Http\Request;
use Concrete\Package\CommunityStoreBccPayway\Entity\ErrorLog;
use Doctrine\ORM\EntityManagerInterface;
use MLocati\PayWay;

defined('C5_EXECUTE') or die('Access Denied');

class Error
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

    public function __construct(Request $request, EntityManagerInterface $entityManager, UserInterface $userInterface)
    {
        $this->request = $request;
        $this->entityManager = $entityManager;
        $this->userInterface = $userInterface;
    }

    public function __invoke()
    {
        $receivedData = new PayWay\Error\RequestData($this->request->query->all());
        $errorLog = new ErrorLog(json_encode($receivedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $errorLog->setRC($receivedData->getRC());
        $this->entityManager->persist($errorLog);
        $this->entityManager->flush();

        return $this->userInterface->buildErrorResponse(
            t('Payment not possible'),
            implode('<br />', [
                t('We are sorry: the payment system is not currently available.'),
                '',
                t('Please try again later.'),
                '',
                '',
                '<small>' . t('Error code: %s', h($errorLog->getRC())) . '</small>',
            ])
        );
    }
}
