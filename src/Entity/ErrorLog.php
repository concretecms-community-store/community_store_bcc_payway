<?php

namespace Concrete\Package\CommunityStoreBccPayway\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(
 *     name="CommunityStoreBccPaywayError",
 *     options={"comment": "Logs of errors on the side of BCC PayWay"}
 * )
 */
class ErrorLog
{
    /**
     * The record ID (null if not yet persisted).
     *
     * @Doctrine\ORM\Mapping\Id
     * @Doctrine\ORM\Mapping\Column(type="integer", options={"unsigned":true, "comment": "Init ID"})
     * @Doctrine\ORM\Mapping\GeneratedValue(strategy="AUTO")
     *
     * @var int|null
     */
    protected $id;

    /**
     * The record creation date/time.
     *
     * @Doctrine\ORM\Mapping\Column(type="datetime", nullable=false, options={"comment": "Record creation date/time"})
     *
     * @var \DateTime
     */
    protected $createdOn;

    /**
     * The received data (in JSON format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Received data (in JSON format)"})
     *
     * @var string
     */
    protected $receivedJson;

    /**
     * The value of the received rc parameter.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length="50", nullable=false, options={"comment": "Value of the resulting rc parameter"})
     *
     * @var string
     */
    protected $rc;

    public function __construct()
    {
        $this->createdOn = new DateTime();
        $this->receivedJson = '';
        $this->rc = '';
        $this->verifyLogs = new ArrayCollection();
    }

    /**
     * Get the record ID (null if not yet persisted).
     *
     * @return int|null
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * Get the record creation date/time.
     *
     * @return \DateTime
     */
    public function getCreatedOn()
    {
        return $this->createdOn;
    }

    /**
     * Get the received data (in JSON format).
     *
     * @return string
     */
    public function getReceivedJson()
    {
        return $this->receivedJson;
    }

    /**
     * Set the received data (in JSON format).
     *
     * @param string $value
     *
     * @return $this
     */
    public function setReceivedJson($value)
    {
        $this->receivedJson = (string) $value;

        return $this;
    }

    /**
     * Get the value of the received rc parameter.
     *
     * @return string
     */
    public function getRC()
    {
        return $this->rc;
    }

    /**
     * Set the value of the received rc parameter.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRC($value)
    {
        $this->rc = (string) $value;

        return $this;
    }
}
