<?php

namespace App\Console\Commands;

use App\Models\Traits\DingTalkNotify;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class T66y extends Command
{
    use DingTalkNotify;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 't66y';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '获取1024地址';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        $response = $client->post('https://get.xunfs.com/app/listapp.php', [
            'verify' => false,
            'headers' => [
                'accept' => '*/*',
                'user-agent' => 'Mozilla/4.0 (compatible; MSIE 6.13; Windows NT 5.1;SV1)'
            ],
            'form_params' => [
                'a' => 'get18',
                'system' => 'android',
                'v' => '2.2.5'
            ]
        ]);

        $result = json_decode($response->getBody()->getContents(), true);

        $content = "url1: {$result['url1']} \n\n url2: {$result['url2']} \n\n url3: {$result['url3']}";

        self::notify('1024 地址', $content);

        dd($result);
    }
}
