<?php

namespace App\Extend\EyCard;

use App\Extend\EyCard\Contracts\Api;
use Illuminate\Support\Str;

class Mch extends Api
{
    protected string $gateway = 'https://mtest.eycard.cn:4443/AG_MerchantManagementSystem_Core/agent/api/gen';
}
