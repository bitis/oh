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

    public array $defaultOption = [
        'version' => '1.0',
        'clientCode' => '61165070',
    ];

    protected array $option = [];

    protected string $gateway = '';

    /**
     * 请求配置
     *
     * @param array $options
     * @return static
     */
    public function setOptions(array $options): Api
    {
        $options = array_merge($this->defaultOption, $options);

        $data = $this->getSignContent($options);

        $options['MAC'] = strtoupper(md5($data));

        $this->option = $options;

        return $this;
    }

    /**
     * 生成签名内容
     * @param array $sign_data
     * @return string
     */
    protected function getSignContent(array $sign_data): string
    {
        ksort($sign_data);
        $params = [];

        foreach ($sign_data as $key => $value) {
            if ($key == 'file' || $key == 'MAC' || empty($value)) {
                continue;
            }
            if (is_array($value)) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
            if ($key != 'sign') {
                $params[] = $key . '=' . $value;
            }
        }

        $params[] = "key=" . config('eycard.channelid');

        return implode("&", $params);
    }

    /**
     * 获取签名
     *
     * @return string
     */
    public function getSign(): string
    {
        if (empty($this->sign))
            $this->sign = $this->signature($this->option);
        return $this->sign;
    }

    public function setSign($sign)
    {
        $this->sign = $sign;
    }

    public function signature($data)
    {
        $data = $this->getSignContent($this->option);
        $this->sign = strtoupper(md5($data));
        return $this;
    }

    /**
     * 发送请求
     *
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request($type = 'form_params')
    {
        $response = $this->http->request('POST', $this->gateway, [$type => $this->option])->getBody()->getContents();
        return json_decode($response, true);
    }
}
