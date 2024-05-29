<?php

declare(strict_types=1);

namespace Concrete\Package\CommunityStoreBccPayway\Src\CommunityStore\Payment\Methods\BccPayway;

use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogEntry;
use Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogProvider as LogProviderContract;
use Concrete\Package\CommunityStoreBccPayway\Entity\ErrorLog;
use Concrete\Package\CommunityStoreBccPayway\Entity\InitLog;
use Concrete\Package\CommunityStoreBccPayway\Entity\VerifyLog;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

defined('C5_EXECUTE') or die('Access Denied.');

class LogProvider implements LogProviderContract
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogProvider::getHandle()
     */
    public function getHandle(): string
    {
        return 'bcc_payway';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogProvider::getName()
     */
    public function getName(): string
    {
        return 'BCC PayWay';
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogProvider::findByDate()
     */
    public function findByDate(DateTimeInterface $fromInclusive, DateTimeInterface $toExclusive): array
    {
        return array_merge(
            $this->findInit(function (QueryBuilder $qb) use ($fromInclusive, $toExclusive): void {
                $dtFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
                $qb
                    ->andWhere('i.createdOn >= :from')
                    ->andWhere('i.createdOn < :to')
                    ->setParameter('from', $fromInclusive->format($dtFormat))
                    ->setParameter('to', $toExclusive->format($dtFormat))
                ;
            }),
            $this->findOrphanVerifyLogs($fromInclusive, $toExclusive),
            $this->findErrors($fromInclusive, $toExclusive)
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogProvider::findByOrderID()
     */
    public function findByOrderID(int $orderID): array
    {
        return $this->findInit(function (QueryBuilder $qb) use ($orderID): void {
            $qb
                ->andWhere('i.associatedOrder = :orderID')
                ->setParameter('orderID', $orderID)
            ;
        });
    }

    /**
     * @return \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogEntry[]
     */
    private function findInit(callable $where): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb
            ->from(InitLog::class, 'i')
            ->leftJoin('i.verifyLogs', 'v')
            ->select('i, v')
            ->addOrderBy('i.createdOn')
            ->addOrderBy('i.id')
            ->addOrderBy('v.createdOn')
            ->addOrderBy('v.id')
        ;
        $where($qb);
        $result = [];
        foreach ($qb->getQuery()->execute() as $initLog) {
            $result = array_merge($result, $this->serializeInitLog($initLog));
        }

        return $result;
    }

    /**
     * @return \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogEntry[]
     */
    private function findOrphanVerifyLogs(DateTimeInterface $fromInclusive, DateTimeInterface $toExclusive): array
    {
        $dtFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $qb = $this->em->createQueryBuilder();
        $qb
            ->from(VerifyLog::class, 'v')
            ->select('v')
            ->andWhere('v.initLog IS NULL')
            ->andWhere('v.createdOn >= :from')
            ->setParameter('from', $fromInclusive->format($dtFormat))
            ->andWhere('v.createdOn < :to')
            ->setParameter('to', $toExclusive->format($dtFormat))
            ->addOrderBy('v.createdOn')
            ->addOrderBy('v.id')
        ;
        $result = [];
        foreach ($qb->getQuery()->execute() as $verifyLog) {
            $result[] = $this->serializeVerifyLog($verifyLog);
        }

        return $result;
    }

    /**
     * @return \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogEntry[]
     */
    private function findErrors(DateTimeInterface $fromInclusive, DateTimeInterface $toExclusive): array
    {
        $dtFormat = $this->em->getConnection()->getDatabasePlatform()->getDateTimeFormatString();
        $qb = $this->em->createQueryBuilder();
        $qb
            ->from(ErrorLog::class, 'e')
            ->select('e')
            ->andWhere('e.createdOn >= :from')
            ->andWhere('e.createdOn < :to')
            ->setParameter('from', $fromInclusive->format($dtFormat))
            ->setParameter('to', $toExclusive->format($dtFormat))
            ->addOrderBy('e.createdOn')
            ->addOrderBy('e.id')
        ;
        $result = [];
        foreach ($qb->getQuery()->execute() as $errorLog) {
            $result[] = $this->serializeErrorLog($errorLog);
        }

        return $result;
    }

    /**
     * @return \Concrete\Package\CommunityStore\Src\CommunityStore\Payment\LogEntry[]
     */
    private function serializeInitLog(InitLog $initLog): array
    {
        $result = [
            new LogEntry(
                $initLog->getCreatedOn(),
                $this->getName() . ($initLog->getEnvironment() === 'sandbox' ? (' (' . t('Test') . ')') : ''),
                t('Payment initialization'),
                $initLog->getAssociatedOrder(),
                [
                    [t('Shop ID'), $initLog->getShopID()],
                    [t('Payment ID'), $initLog->getPaymentID()],
                    [t('Data sent to'), $initLog->getRequestUrl()],
                    [t('Data sent'), $this->formatJsonObjectString($initLog->getRequestJson())],
                    [t('Data received'), $this->formatJsonObjectString($initLog->getResponseJson())],
                ],
                $initLog->getException()
            ),
        ];
        foreach ($initLog->getVerifyLogs() as $verifyLog) {
            $result[] = $this->serializeVerifyLog($verifyLog);
        }

        return $result;
    }

    private function serializeVerifyLog(VerifyLog $verifyLog): LogEntry
    {
        $initLog = $verifyLog->getInitLog();
        switch ($verifyLog->getPlace()) {
            case 'callbackURL':
                $type = t('Server-to-server communication');
                break;
            case 'notifyURL':
                $type = t('Customer fulfilled form');
                break;
            default:
                $type = $verifyLog->getPlace();
        }

        return new LogEntry(
            $verifyLog->getCreatedOn(),
            $this->getName() . ($initLog !== null && $initLog->getEnvironment() === 'sandbox' ? (' (' . t('Test') . ')') : ''),
            $type,
            $initLog === null ? null : $initLog->getAssociatedOrder(),
            [
                [t('Data Received'), $this->formatJsonObjectString($verifyLog->getReceivedJson())],
                [t('Request URL'), $verifyLog->getRequestUrl()],
                [t('Request Data'), $this->formatJsonObjectString($verifyLog->getRequestJson())],
                [t('Response Data'), $this->formatJsonObjectString($verifyLog->getResponseJson())],
                ['RC', $verifyLog->getRC()],
            ],
            $verifyLog->getException()
        );
    }

    private function serializeErrorLog(ErrorLog $errorLog): LogEntry
    {
        return new LogEntry(
            $errorLog->getCreatedOn(),
            $this->getName(),
            t('Error'),
            null,
            [
                [t('Data Received'), $this->formatJsonObjectString($errorLog->getReceivedJson())],
                ['RC', $errorLog->getRC()],
            ],
            t('Error raised by payment gateway')
        );
    }

    private function formatJsonObjectString(?string $json): ?string
    {
        if ($json === null || $json === '') {
            return null;
        }
        $data = json_decode($json);
        if (!$data instanceof \stdClass) {
            return null;
        }
        return $this->formatJsonObject($data);
    }

    private function formatJsonObject(?object $data): ?string
    {
        return $data === null ? null : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_LINE_TERMINATORS);
    }
}
