<?php

namespace App\Extend\EyCard;

use App\Extend\EyCard\Contracts\Api;
use GuzzleHttp\Client;

class Mch extends Api
{
    protected $gateway = 'https://mtest.eycard.cn:4443/AG_MerchantManagementSystem_Core/agent/api/gen';

    public function gen()
    {
        $options = [
            'version' => '1.0',
            'messageType' => 'AGMERAPPLY',
            'clientCode' => '',
            'MAC' => '',
            'backUrl' => '',
            'merInfo' => '',
            'plusInfo' => '',
            'sysInfo' => '',
            'licInfo' => '',
            'accInfo' => '',
            'accInfoBak' => '',
            'funcInfo' => '',
            'prodList' => '',
            'operaTrace' => '',
            'isContract' => '',
            'retCode' => '',
            'retMsg' => '',
            'merTrace' => '',
            'customInfo' => '',
        ];
    }

    /**
     * 生成内容签名
     * @param $data
     * @return string
     */
    protected function getSign($data)
    {

        return strtoupper(md5($data));

    }
}
