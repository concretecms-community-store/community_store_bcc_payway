<?php

namespace Concrete\Package\CommunityStoreBccPayway\Service\Http\Driver;

use Concrete\Core\Http\Client\Client;
use Exception;
use MLocati\PayWay\Exception\NetworkUnavailable;
use MLocati\PayWay\Http\Driver as PayWayDriver;
use MLocati\PayWay\Http\Response;

defined('C5_EXECUTE') or die('Access Denied');

class V9 implements PayWayDriver
{
    /**
     * @var \Concrete\Core\Http\Client\Client|\GuzzleHttp\Client
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
        $options = ['http_errors' => false];
        if ($headers !== []) {
            $options['headers'] = $headers;
        }
        if ($body !== '') {
            $options['body'] = $body;
        }
        try {
            $response = $this->client->request($method, $url, $options);
        } catch (Exception $x) {
            throw new NetworkUnavailable($x->getMessage());
        }

        return new Response(
            $response->getStatusCode(),
            $this->simplifyHeaders($response->getHeaders()),
            $response->getBody()->getContents()
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
