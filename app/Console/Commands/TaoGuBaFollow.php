<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;

class TaoGuBaFollow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TaoGuBa:Notify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
    public function handle()
    {
        $cookies = CookieJar::fromArray($this->cookie(), '.taoguba.com.cn');

        $client = new Client([
            'debug' => false,
            'cookies' => $cookies,
            'allow_redirects' => false,
            'headers' => [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
                'accept-encoding' => 'gzip, deflate, br',
                'accept-language' => 'zh,zh-CN;q=0.9,en-US;q=0.8,en;q=0.7,zh-TW;q=0.6,ja;q=0.5',
                'cache-control' => 'max-age=0',
                'dnt' => '1',
                'referer' => 'https://www.taoguba.com.cn/user/blog/moreReply?userID=2101931',
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
//            'on_headers' => function (ResponseInterface $response) {
//                $cookies = $response->getHeader('Set-Cookie');
//                $this->cookieHandle($cookies);
//                $this->refreshCookie = !$this->refreshCookie;
//                if ($this->refreshCookie) $this->refreshCookies();
//            }
        ]);
        $url = "https://www.taoguba.com.cn/user/blog/moreReply?pageNo=1&userID=2101931";
        $response = $client->get($url);

        $html = $response->getBody()->getContents();

        $crawler = new Crawler($html);

        $crawler->filter('.blogReply')->each(function (Crawler $reply) use ($client) {
            $userName = $reply->filter('.blogReply-userName')->text();
            $date = $reply->filter('.blogReply-date')->text();
            $gray = $reply->filter('.blogReply-gray')->text();
            $from = $reply->filter('.blogReply-from>a')->text();
            $replyContent = $reply->filter('.blogReply-subinfo')->text();
            $fromHref = "https://www.taoguba.com.cn/" . $reply->filter('.blogReply-from>a')->attr('href');
            $replyHref = "https://www.taoguba.com.cn/" . $reply->filter('.blogReply-subinfo')->attr('href');

            $replyHtml = $client->get($replyHref)->getBody()->getContents();

            $subject = (new Crawler($replyHtml))->filter('#gioMsg_R_' . Str::after($replyHref, '#'))->attr('subject');

            $imageHref = '';
            if ($images = Str::of($subject)->matchAll('/(https:\/\/image.taoguba.com.cn\/img\/[^"]+)_max.png/')) {
                foreach ($images as $image) {
                    $imageHref .= $image . "\n";
                }
                $imageHref = trim($imageHref);
            }

            $log = sprintf("%s 与 %s %s %s \n %s %s", $userName, $date, $gray, $from, $replyContent, $imageHref);
            $this->info($log);
            $this->newLine();
        });

        return 0;
    }

    public function cookie(): array
    {
        $cookies = [];
        $cookieText = Cache::get('TGB_COOkIE');
        foreach (explode('; ', $cookieText) as $str) {
            list($k, $v) = explode('=', $str);
            $cookies[$k] = $v;
        }

        return $cookies;
    }
}
