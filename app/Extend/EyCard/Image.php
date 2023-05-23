<?php

namespace App\Extend\EyCard;

use App\Extend\EyCard\Contracts\Api;

class Image extends Api
{
    public array $option = [];

    public string $gateway = 'https://mtest.eycard.cn:4443/AG_MerchantManagementSystem_Core/agent/api/imgUpl';

    /**
     * 图片类型
     */
    const PIC_MODEL_MAP = [
        '01' => '法人身份证人像面',
        '02' => '法人身份证国徽面',
        '03' => '商户联系人身份证人像面',
        '04' => '商户联系人身份证国徽面',
        '05' => '营业执照',
        '06' => '收银台',
        '07' => '内部环境照',
        '08' => '营业场所门头照',
        '09' => '门牌号',
        '10' => '协议（指线下签订的纸质协议）',
        '11' => '尽调材料一（选填）',
        '12' => '尽调材料二（选填）',
        '13' => '开户许可证',
        '14' => '银行卡',
        '15' => '清算授权书',
        '16' => '定位照（选填）',
        '17' => '手持身份证照片（选填）',
        '18' => '收款人身份证人像面（选填）',
        '19' => '收款人身份证国徽面（选填）',
        '20' => '持清算授权书（选填）',
        '21' => '特殊行业许可证（选填）'
    ];

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

        $tmp = [];
        foreach ($options as $k => $v) {
            if ($k == 'file') continue;
            $tmp[] = [
                'name' => $k,
                'contents' => $v,
            ];
        }

        $tmp_files[] = [
            'name' => 'fileName',
            'contents' => base64_encode($options['file']->getContent()),
            'filename' => $options['file']->getClientOriginalName()
        ];

        $this->option = array_merge($tmp, $tmp_files);;

        return $this;
    }
}
