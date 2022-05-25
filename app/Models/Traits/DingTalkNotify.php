<?php

namespace App\Models\Traits;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

trait DingTalkNotify
{
    static function notify($title, $content) {
        $dingtalk = "https://oapi.dingtalk.com/robot/send?access_token=" . config('watch.dingtalk_token');

        try {
            (new Client)->post($dingtalk, [
                'json' => [
                    "msgtype" => "markdown",
                    "at" => [
                        "atMobiles" => [],
                        "isAtAll" => true
                    ],
                    "markdown" => [
                        "title" => $title . " -- " . config('watch.dingtalk_keyword'),
                        "text" => $content,
                    ]
                ]
            ]);

        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }
}
