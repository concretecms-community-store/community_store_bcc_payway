<?php

namespace Concrete\Package\CommunityStoreBccPayway\Callback;

use Concrete\Core\Http\Request;
use Concrete\Core\Http\Response;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Package\CommunityStoreBccPayway;
use MLocati\PayWay;

defined('C5_EXECUTE') or die('Access Denied');

class Server2Server
{
    /**
     * @var \Concrete\Core\Http\Request
     */
    protected $request;

    /**
     * @var \Concrete\Package\CommunityStoreBccPayway\PayWayVerifier
     */
    protected $payWayVerifier;

    /**
     * @var \Concrete\Core\Http\ResponseFactoryInterface
     */
    protected $responseFactory;

    public function __construct(Request $request, CommunityStoreBccPayway\PayWayVerifier $payWayVerifier, ResponseFactoryInterface $responseFactory)
    {
        $this->request = $request;
        $this->payWayVerifier = $payWayVerifier;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke()
    {
        $receivedData = new PayWay\Server2Server\RequestData($this->request->request->all());
        $ok = $this->payWayVerifier->verifyFromCallbackURL($receivedData);

        return $this->responseFactory->create(
            $ok ? 'ok' : 'ko',
            $ok ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY,
            ['Content-Type' => 'text/plain']
        );
    }
}
