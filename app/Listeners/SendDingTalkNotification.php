<?php

namespace App\Listeners;

use App\Events\SystemError;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SendDingTalkNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param \App\Events\SystemError $error
     * @return void
     */
    public function handle(SystemError $error)
    {
        $message = $error->throwable->getMessage();

        $timestamp = time() * 1000;
        $secret = config('dingtalk.secret');

        $sign = urlencode(base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true)));

        $dingtalk = "https://oapi.dingtalk.com/robot/send?access_token=" . config('dingtalk.access_token')
            . "&timestamp=" . $timestamp . "&sign=" . $sign;

        try {
            (new Client)->post($dingtalk, [
                'json' => [
                    "msgtype" => "markdown",
                    "at" => [
                        "atMobiles" => [],
                        "isAtAll" => true
                    ],
                    "markdown" => [
                        "title" => $message,
                        "text" => $message
                            . "\r\n\r\n"
                            . "File: " . $error->throwable->getFile()
                            . "\r\n\r\n"
                            . 'Line: ' . $error->throwable->getLine()
                            . "\r\n\r\n"
                            . 'Path: ' . \request()->path()
                            . "\r\n\r\n"
                            . 'Method: ' . \request()->method()
                            . "\r\n\r\n"
                            . 'Request: ' . "```json\r\n" . json_encode(request()->request->all()) . "\r\n```"
                            . "\r\n\r\n"
                            . 'Headers: ' . json_encode(Arr::only(request()->headers->all(), ['origin', 'storeid', 'apitoken', 'openid', 'usertoken', 'stafftoken', 'suppliertoken']))
                    ]
//                            . $error->exception->getTraceAsString()
                ]
            ]);

        } catch (\Exception $exception) {
            Log::info($exception->getMessage());
        }
    }
}
