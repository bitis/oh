<?php

namespace App\Extend\EyCard\Contracts;

use GuzzleHttp\Client;

class Api
{
    /**
     * @var Client
     */
    protected $http;

    public function __construct()
    {
        $this->http = new Client(['verify' => false]);
    }

    public array $option = [
        'version' => '1.0',
        'clientCode' => '',
        ''
    ];

    protected $gateway = '';

    /**
     * 请求配置
     *
     * @param array $options
     * @return static
     */
    public function setOptions(array $options)
    {
        $this->option = $options;
        return $this;
    }

    /**
     * 发送请求
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request()
    {
        $response = $this->http->request('POST', $this->gateway, $this->option)->getBody()->getContents();
        return json_decode($response, true);
    }
}
