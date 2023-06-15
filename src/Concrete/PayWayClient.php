<?php

namespace Concrete\Package\CommunityStoreBccPayway;

use MLocati\PayWay;

defined('C5_EXECUTE') or die('Access Denied');

class PayWayClient extends PayWay\Client
{
    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $terminalID;

    /**
     * @param string $environment
     * @param string $terminalID
     * @param string $servicesUrl
     * @param string $signatureKey
     */
    public function __construct($environment, $terminalID, $servicesUrl, $signatureKey, PayWay\Http\Driver $driver)
    {
        parent::__construct($servicesUrl, $signatureKey, $driver);
        $this->environment = (string) $environment;
        $this->terminalID = (string) $terminalID;
    }

    /**
     * @return string
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return string
     */
    public function getTerminalID()
    {
        return $this->terminalID;
    }
}
