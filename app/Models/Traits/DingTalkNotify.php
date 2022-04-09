<?php

namespace App\Models\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

trait DingTalkNotify
{
    function notify($title, $content) {
        $dingtalk = "https://oapi.dingtalk.com/robot/send?access_token=d7b86bbac8aca5a62df3d8e2a9dad9eea70e954e76e79d59fc7716145c7dd229";

        try {
            (new Client)->post($dingtalk, [
                'json' => [
                    "msgtype" => "markdown",
                    "at" => [
                        "atMobiles" => [
                            "15138674502"
                        ],
                        "isAtAll" => false
                    ],
                    "markdown" => [
                        "title" => $title,
                        "text" => $content,
                    ]
                ]
            ]);

        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }
}
