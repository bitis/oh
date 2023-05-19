<?php

namespace App\Jobs;

use EasyWeChat\Factory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 发送微信模板消息
 */
class TemplateMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var array 模板消息数据
     */
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = config("wechat.config1");
        $app = Factory::officialAccount($config);

        $result = $app->template_message->send($this->data);

        if ($result["errmsg"] != "ok") $this->fail(new \Exception(json_encode($result)));
    }
}
