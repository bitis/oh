<?php

namespace App\Console\Commands;

use App\Mail\Nga;
use App\Mail\NgaSign;
use App\Models\Cookie;
use App\Models\NgaFollow;
use App\Models\NgaReply;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class NgaNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Nga:Notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Nga 提醒';

    /**
     * @var array 用户ID
     */
    protected array $follow = [];

    /**
     * @var Client
     */
    protected $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $cookies = CookieJar::fromArray($this->cookie(), '.nga.178.com');
        $this->client = new Client([
            'debug' => false,
            'cookies' => $cookies,
            'allow_redirects' => false,
            'headers' => [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'accept-encoding' => 'gzip, deflate, br',
                'accept-language' => 'zh,zh-CN;q=0.9,en-US;q=0.8,en;q=0.7,zh-TW;q=0.6,ja;q=0.5',
                'cache-control' => 'max-age=0',
                'dnt' => '1',
                'sec-ch-ua' => 'Chromium";v="110", "Not A(Brand";v="24", "Google Chrome";v="110"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => 'Windows',
                'sec-fetch-dest' => 'document',
                'sec-fetch-mode' => 'navigate',
                'sec-fetch-site' => 'cross-site',
                'sec-fetch-user' => '?1',
                'upgrade-insecure-requests' => 1,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36'
            ],
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(Client $client)
    {
        foreach ($this->follow() as $uid) {
            $this->sign($uid);

            $this->reply($uid);
        }
        return 0;
    }

    public function sign($uid)
    {
        $response = $this->client->get("https://nga.178.com/nuke.php?__lib=ucp&__act=get&lite=js&uid=" . $uid, [
            'headers' => [
                'Referer' => 'https://nga.178.com/nuke.php?func=ucp&uid=' . $uid
            ]
        ]);
        $content = iconv('GBK', 'UTF-8', $response->getBody()->getContents());
        $profile = json_decode(str_replace('window.script_muti_get_var_store=', '', $content), true);

        $cache_key = 'nga_sign_' . $uid;

        $sign = $profile['data'][0]['sign'];

        if (cache()->get($cache_key, '') != $sign) {
            Mail::to(config('mail.recipient'))->send(new NgaSign($profile['data'][0]['username'], $sign));
            cache()->put($cache_key, $sign);
        }
    }

    public function cookie(): array
    {
        $cookies = [];
        $cookieText = Cookie::where('scope', 'NGA')->value('content');
        foreach (explode('; ', $cookieText) as $str) {
            list($k, $v) = explode('=', $str);
            $cookies[$k] = $v;
        }

        return $cookies;
    }

    public function follow()
    {
        return NgaFollow::pluck('uid');
    }

    public function reply($uid) {
        $response = $this->client->get('https://nga.178.com/thread.php', [
            'query' => [
                'authorid' => $uid,
                'searchpost' => 1,
                'page' => 1,
                'lite' => 'js',
                'noprefix' => ''
            ]
        ]);
        $contents = json_decode(iconv('GBK', 'UTF-8', $response->getBody()->getContents()), true);

        foreach ($contents['data']['__T'] as $content) {
            if (!$m_reply = NgaReply::where('reply_id', $content['__P']['pid'])->first()) {
                $m_reply = NgaReply::create([
                    'reply_id' => $content['__P']['pid'],
                    'content' => $content['__P']['content'],
                    'author' => $content['author'],
                    'authorid' => $content['authorid'],
                    'subject' => $content['subject'],
                    'subject_id' => $content['__P']['tid'],
                    'postdate' => date('Y-m-d H:i:s', $content['__P']['postdate']),
                    'notified' => 0
                ]);

                $log = sprintf("%s 于 %s 回复 %s \n %s", $content['author'], date('Y-m-d H:i:s', $content['__P']['postdate']), $content['subject'], $content['__P']['content']);
                $this->info($log);
                $this->newLine();
            }
            if (!$m_reply->notified) {
                Mail::to(config('mail.recipient'))->send(new Nga($m_reply));
                $m_reply->notified = 1;
                $m_reply->save();
            }
        }
    }
}
