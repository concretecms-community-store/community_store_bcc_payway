<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service\Http;

use Concrete\Core\Application\Application;
use Concrete\Core\Config\Repository\Repository;

defined('C5_EXECUTE') or die('Access Denied');

class ClientFactory
{
    /**
     * @var int
     */
    protected $coreMajorVersion;

    /**
     * @var \Concrete\Core\Application\Application
     */
    protected $clientMaker;

    public function __construct(Repository $config, Application $clientMaker)
    {
        $version = $config->get('concrete.version');
        list($majorVersion) = explode('.', $version, 2);
        $this->coreMajorVersion = (int) $majorVersion;
        $this->clientMaker = $clientMaker;
    }

    /**
     * @return \MLocati\PayWay\Http\Driver
     */
    public function createDriver()
    {
        if ($this->coreMajorVersion >= 9) {
            return $this->clientMaker->make(Driver\V9::class);
        }

        return $this->clientMaker->make(Driver\V8::class);
    }
}
