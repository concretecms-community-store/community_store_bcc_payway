<?php

namespace Concrete\Package\CommunityStoreBccPayway;

use Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface;
use Concrete\Core\Database\EntityManager\Provider\StandardPackageProvider;
use Concrete\Core\Package\Package;
use Concrete\Core\Routing\Router;
use Concrete\Package\CommunityStore\Src\CommunityStore;

defined('C5_EXECUTE') or die('Access Denied');

class Controller extends Package implements ProviderAggregateInterface
{
    const PAYMENTMETHOD_HANDLE = 'bcc_payway';

    const PATH_CALLBACK_CUSTOMER = '/ccm/community_store/bcc_payway/callback/customer';

    const PATH_CALLBACK_SERVER2SERVER = '/ccm/community_store/bcc_payway/callback/server2server';

    const PATH_CALLBACK_ERROR = '/ccm/community_store/bcc_payway/callback/error';

    protected $pkgHandle = 'community_store_bcc_payway';

    protected $pkgVersion = '1.0.1';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$appVersionRequired
     */
    protected $appVersionRequired = '8';

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$packageDependencies
     */
    protected $packageDependencies = ['community_store' => '2.5'];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::$pkgAutoloaderRegistries
     */
    protected $pkgAutoloaderRegistries = [];

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageName()
     */
    public function getPackageName()
    {
        return t('BCC PayWay Payment Method');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::getPackageDescription()
     */
    public function getPackageDescription()
    {
        return t('BCC PayWay Payment Method for Community Store');
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Database\EntityManager\Provider\ProviderAggregateInterface::getEntityManagerProvider()
     */
    public function getEntityManagerProvider()
    {
        return new StandardPackageProvider($this->app, $this, [
            'src/Entity' => 'Concrete\\Package\\CommunityStoreBccPayway\\Entity',
        ]);
    }

    public function on_start()
    {
        $this->registerAutoload();
        if (!$this->app->isRunThroughCommandLineInterface()) {
            $this->registerRoutes();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::install()
     */
    public function install()
    {
        $this->registerAutoload();
        $pkg = parent::install();
        CommunityStore\Payment\Method::add(self::PAYMENTMETHOD_HANDLE, 'BCC PayWay', $pkg);
    }

    /**
     * {@inheritdoc}
     *
     * @see \Concrete\Core\Package\Package::uninstall()
     */
    public function uninstall()
    {
        $pm = CommunityStore\Payment\Method::getByHandle(self::PAYMENTMETHOD_HANDLE);
        if ($pm) {
            $pm->delete();
        }
        parent::uninstall();
    }

    private function registerAutoload()
    {
        $file = $this->getPackagePath() . '/vendor/autoload.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }

    private function registerRoutes()
    {
        $router = $this->app->make(Router::class);
        $router->get(static::PATH_CALLBACK_CUSTOMER, [Callback\Customer::class, '__invoke']);
        $router->post(static::PATH_CALLBACK_SERVER2SERVER, [Callback\Server2Server::class, '__invoke']);
        $router->get(static::PATH_CALLBACK_ERROR, [Callback\Error::class, '__invoke']);
    }
}
