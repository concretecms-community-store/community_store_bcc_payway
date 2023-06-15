<?php

namespace Concrete\Package\CommunityStoreBccPayway\Http\Driver;

use Concrete\Core\Http\Client\Client;
use Exception;
use MLocati\PayWay\Exception\NetworkUnavailable;
use MLocati\PayWay\Http\Driver as PayWayDriver;
use MLocati\PayWay\Http\Response;
use Zend\Http\Request as ZendRequest;

defined('C5_EXECUTE') or die('Access Denied');

class V8 implements PayWayDriver
{
    /**
     * @var \Concrete\Core\Http\Client\Client|\Zend\Http\Client
     */
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * {@inheritdoc}
     *
     * @see \MLocati\PayWay\Http\Driver::send()
     */
    public function send($url, $method, array $headers, $body = '')
    {
        $this->client->reset();
        $request = new ZendRequest();
        $request->setMethod($method);
        $request->setUri($url);
        $requestHeaders = $request->getHeaders();
        foreach ($headers as $name => $value) {
            $requestHeaders->addHeaderLine($name, (string) $value);
        }
        if ($body !== '') {
            $request->setContent($body);
        }
        try {
            $response = $this->client->send($request);
        } catch (Exception $x) {
            throw new NetworkUnavailable($x->getMessage());
        }

        return new Response(
            $response->getStatusCode(),
            $this->simplifyHeaders($response->getHeaders()->toArray()),
            $response->getBody()
        );
    }

    /**
     * @return array
     */
    protected function simplifyHeaders(array $headers)
    {
        return array_map(static function ($values) {
            return is_array($values) && count($values) === 1 ? $values[0] : $values;
        }, $headers);
    }
}
