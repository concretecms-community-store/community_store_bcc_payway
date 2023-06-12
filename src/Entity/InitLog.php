<?php

namespace Concrete\Package\CommunityStoreBccPayway\Entity;

use Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(
 *     name="CommunityStoreBccPaywayInit",
 *     options={"comment": "Init requests for BCC PayWay payment method"}
 * )
 */
class InitLog
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
     * The order associated to this request.
     *
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order")
     * @Doctrine\ORM\Mapping\JoinColumn(name="associatedOrder", referencedColumnName="oID", nullable=false, onDelete="CASCADE")
     *
     * @var \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order
     */
    protected $associatedOrder;

    /**
     * The value of the shopID parameter that uniquely identifies the request.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=64, nullable=false, unique=true, options={"comment": "Value of the shopID parameter that uniquely identifies the request"})
     *
     * @var string
     */
    protected $shopID;

    /**
     * The request (in JSON format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Request (in JSON format)"})
     *
     * @var string
     */
    protected $requestJson;

    /**
     * The request URL.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Request URL"})
     *
     * @var string
     */
    protected $requestUrl;

    /**
     * The request (in XML format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Request (in XML format)"})
     *
     * @var string
     */
    protected $requestXml;

    /**
     * The original response (should be in XML format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Original response (in XML format)"})
     *
     * @var string
     */
    protected $rawResponse;

    /**
     * The parsed response (in JSON format).
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Parsed response (in JSON format)"})
     *
     * @var string
     */
    protected $responseJson;

    /**
     * The exception thrown during the init() request.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Exception thrown during the init() request"})
     *
     * @var string
     */
    protected $exception;

    /**
     * The remote service-assigned payment ID.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length=256, nullable=false, options={"comment": "Remote service-assigned payment ID"})
     *
     * @var string
     */
    protected $paymentID;

    /**
     * The VerifyLog instances associated to this InitLog.
     *
     * @Doctrine\ORM\Mapping\OneToMany(targetEntity="VerifyLog", mappedBy="initLog")
     * @Doctrine\ORM\Mapping\OrderBy({"createdOn"="ASC", "id"="ASC"})
     *
     * @var \Doctrine\Common\Collections\Collection|\Concrete\Package\CommunityStoreBccPayway\Entity\VerifyLog[]
     */
    protected $verifyLogs;

    /**
     * @param string $shopID
     */
    public function __construct(Order $associatedOrder, $shopID)
    {
        $this->createdOn = new DateTime();
        $this->associatedOrder = $associatedOrder;
        $this->shopID = $shopID;
        $this->requestJson = '';
        $this->requestUrl = '';
        $this->requestXml = '';
        $this->rawResponse = '';
        $this->responseJson = '';
        $this->exception = '';
        $this->paymentID = '';
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
     * Get the order associated to this request.
     *
     * @return \Concrete\Package\CommunityStore\Src\CommunityStore\Order\Order
     */
    public function getAssociatedOrder()
    {
        return $this->associatedOrder;
    }

    /**
     * Get the value of the shopID parameter that uniquely identifies the request.
     *
     * @return string
     */
    public function getShopID()
    {
        return $this->shopID;
    }

    /**
     * Get the request (in JSON format).
     *
     * @return string
     */
    public function getRequestJson()
    {
        return $this->requestJson;
    }

    /**
     * Set the request (in JSON format).
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRequestJson($value)
    {
        $this->requestJson = (string) $value;

        return $this;
    }

    /**
     * Get the request URL.
     *
     * @return string
     */
    public function getRequestUrl()
    {
        return $this->requestUrl;
    }

    /**
     * Set the request URL.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRequestUrl($value)
    {
        $this->requestUrl = (string) $value;

        return $this;
    }

    /**
     * Get the request (in XML format).
     *
     * @return string
     */
    public function getRequestXml()
    {
        return $this->requestXml;
    }

    /**
     * Set the request (in XML format).
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRequestXml($value)
    {
        $this->requestXml = (string) $value;

        return $this;
    }

    /**
     * Get the original response (should be in XML format).
     *
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * Set the original response (should be in XML format).
     *
     * @param string $value
     *
     * @return $this
     */
    public function setRawResponse($value)
    {
        $this->rawResponse = (string) $value;

        return $this;
    }

    /**
     * Get the parsed response (in JSON format).
     *
     * @return string
     */
    public function getResponseJson()
    {
        return $this->responseJson;
    }

    /**
     * Set the parsed response (in JSON format).
     *
     * @param string $value
     *
     * @return $this
     */
    public function setResponseJson($value)
    {
        $this->responseJson = (string) $value;

        return $this;
    }

    /**
     * Get the exception thrown during the init() request.
     *
     * @return string
     */
    public function getException()
    {
        return $this->exception;
    }

    /**
     * Set the exception thrown during the init() request.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setException($value)
    {
        $this->exception = (string) $value;

        return $this;
    }

    /**
     * Get the remote service-assigned payment ID.
     *
     * @return string
     */
    public function getPaymentID()
    {
        return $this->paymentID;
    }

    /**
     * Set the remote service-assigned payment ID.
     *
     * @param string $value
     *
     * @return $this
     */
    public function setPaymentID($value)
    {
        $this->paymentID = (string) $value;

        return $this;
    }

    /**
     * Get the VerifyLog instances associated to this InitLog.
     *
     * @return \Doctrine\Common\Collections\Collection|\Concrete\Package\CommunityStoreBccPayway\Entity\VerifyLog[]
     */
    public function getVerifyLogs()
    {
        return $this->verifyLogs;
    }
}
