<?php

namespace Concrete\Package\CommunityStoreBccPayway\Entity;

use DateTime;

defined('C5_EXECUTE') or die('Access Denied.');

/**
 * @Doctrine\ORM\Mapping\Entity
 * @Doctrine\ORM\Mapping\Table(
 *     name="CommunityStoreBccPaywayVerify",
 *     options={"comment": "Verify requests for BCC PayWay payment method"}
 * )
 */
class VerifyLog
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
     * The init associated to this request.
     *
     * @Doctrine\ORM\Mapping\ManyToOne(targetEntity="InitLog", inversedBy="verifyLogs")
     * @Doctrine\ORM\Mapping\JoinColumn(name="initLog", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     *
     * @var \Concrete\Package\CommunityStoreBccPayway\Entity\InitLog|null
     */
    protected $initLog;

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
     * The value of the resulting rc parameter.
     *
     * @Doctrine\ORM\Mapping\Column(type="string", length="50", nullable=false, options={"comment": "Value of the resulting rc parameter"})
     *
     * @var string
     */
    protected $rc;

    /**
     * The exception thrown during the init() request.
     *
     * @Doctrine\ORM\Mapping\Column(type="text", nullable=false, options={"comment": "Exception thrown during the init() request"})
     *
     * @var string
     */
    protected $exception;

    /**
     * @param string $receivedJson
     */
    public function __construct($receivedJson)
    {
        $this->createdOn = new DateTime();
        $this->receivedJson = (string) $receivedJson;
        $this->requestJson = '';
        $this->requestUrl = '';
        $this->requestXml = '';
        $this->rawResponse = '';
        $this->responseJson = '';
        $this->rc = '';
        $this->exception = '';
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
     * Get the init associated to this request.
     *
     * @return \Concrete\Package\CommunityStoreBccPayway\Entity\InitLog|null
     */
    public function getInitLog()
    {
        return $this->initLog;
    }

    /**
     * Get the init associated to this request.
     *
     * @return $this
     */
    public function setInitLog(InitLog $value = null)
    {
        $this->initLog = $value;

        return $this;
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
     * Get the value of the resulting rc parameter.
     *
     * @return string
     */
    public function getRC()
    {
        return $this->rc;
    }

    /**
     * Set the value of the resulting rc parameter.
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
}
