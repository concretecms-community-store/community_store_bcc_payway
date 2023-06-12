<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service;

use Concrete\Core\Config\Repository\Repository;
use MLocati\PayWay;

defined('C5_EXECUTE') or die('Access Denied');

class PayWayClientFactory
{
    /**
     * @var \Concrete\Core\Config\Repository\Repository
     */
    protected $config;

    /**
     * @var \Concrete\Package\CommunityStoreBccPayway\Service\Http\ClientFactory
     */
    protected $httpClientFactory;

    public function __construct(Repository $config, Http\ClientFactory $httpClientFactory)
    {
        $this->config = $config;
        $this->httpClientFactory = $httpClientFactory;
    }

    /**
     * @param string $terminalID [out] the terminal ID to be used
     *
     * @return \MLocati\PayWay\Client
     */
    public function buildClient(&$terminalID = '')
    {
        return $this->buildClientForEnvironment($this->config->get('community_store_bcc_payway::options.environment'), $terminalID);
    }

    /**
     * @param string $terminalID the terminal ID for which you want the client
     *
     * @return \MLocati\PayWay\Client|null NULL if $terminalID couldn't be found
     */
    public function buildClientByTerminalID($terminalID)
    {
        $environmentsData = $this->config->get('community_store_bcc_payway::options.environments');
        $environmentKeys = is_array($environmentsData) ? array_keys($environmentsData) : [];
        foreach ($environmentKeys as $environmentKey) {
            $terminalIDForEnvironment = (string) $this->config->get("community_store_bcc_payway::options.environments.{$environmentKey}.terminalID");
            if ($terminalIDForEnvironment === $terminalID) {
                return $this->buildClientForEnvironment($environmentKey);
            }
        }

        return null;
    }

    /**
     * @param string $environmentKey
     * @param string $terminalID [out] the terminal ID to be used
     *
     * @return \MLocati\PayWay\Client
     */
    protected function buildClientForEnvironment($environmentKey, &$terminalID = '')
    {
        $terminalID = (string) $this->config->get("community_store_bcc_payway::options.environments.{$environmentKey}.terminalID");

        return new PayWay\Client(
            $this->config->get("community_store_bcc_payway::options.environments.{$environmentKey}.servicesURL"),
            $this->config->get("community_store_bcc_payway::options.environments.{$environmentKey}.signatureKey"),
            $this->httpClientFactory->createDriver()
        );
    }
}
