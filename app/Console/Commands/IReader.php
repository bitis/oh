<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class IReader extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iReader {bookId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '下载掌阅电子书';


    protected $workPath = 'iReader/';

    protected $bookId;

    /**
     * @var Client
     */
    private $client;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $cookieText = config('ireader.cookieText');
        $cookieArr = [];
        foreach (explode('; ', $cookieText) as $item) {
            list($key, $val) = explode('=', $item);
            $cookieArr[$key] = $val;
        }

        $cookies = CookieJar::fromArray($cookieArr, '.ireader.com.cn');

        $this->client = new Client([
            'debug' => false,
            'cookies' => $cookies,
            'headers' => [
                'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/106.0.0.0 Safari/537.36'
            ]
        ]);
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->bookId = $this->argument('bookId');

        $chapters = $this->getChapterList();

        $bookFile = $this->workPath . $this->bookId . '.html';

        foreach ($chapters as $chapter) {
            $content = $this->getContent($chapter['id']);

            if ($chapter['id'] != 1)
                $this->appendOrAbort($bookFile, $content);

            sleep(1);
            $this->info($chapter['id']);
        }

        $this->buildPdf();

        return 0;
    }

    public function getChapterList(): array
    {
        $list = [];

        $page = 1;

        do {
            $response = $this->client->get("https://www.ireader.com.cn/index.php?ca=Chapter.List&ajax=1&bid={$this->bookId}&page={$page}&pageSize=100");

            $contents = $response->getBody()->getContents();

            Storage::put($this->workPath . $this->bookId . '/Chapter' . $page . '.json', $contents);

            $result = json_decode($contents, true);

            $list[] = $result['list'];

            $page++;

            sleep(1);
        } while ($page < $result['page']['totalPage']);

        return Arr::collapse($list);
    }

    public function getContent($id): string
    {
        $response = $this->client->get("https://www.ireader.com.cn/index.php?ca=Chapter.Content&bid={$this->bookId}&cid={$id}");

        $contents = $response->getBody()->getContents();

        Storage::put($this->workPath . $this->bookId . '/content/' . str_repeat('0', 3 - strlen($id)) . $id . '.html', $contents);

        return $contents;
    }

    public function appendOrAbort($bookFile, $content)
    {
        if (!Storage::exists($bookFile)) {
            $template = $this->buildTemplate($content);
            Storage::put($bookFile, $template);
        }

        $crawler = new Crawler($content);

        $body = $crawler->filter('body')->first()->children()->outerHtml();

        $images = $crawler->filter('img')->each(function (Crawler $image) {
            return str_replace('&', '&amp;', $image->attr('src'));
        });

        $replace = [];

        foreach ($images as $image) {
            $replace[] = 'https://book.img.zhangyue01.com' . parse_url($image)['path'];
        }

        $body = Str::replace($images, $replace, $body);

        $book = Storage::get($bookFile);

        if (!Str::contains($book, $body)) {
            $body = $body . '<div style="page-break-after:always;"></div>' . "\r\n";

            Storage::append($bookFile, $body);
        }
    }

    public function buildTemplate($content): string
    {
        $crawler = new Crawler($content);

        $link = $crawler->filter("link");

        $href = $link->first()->attr('href');

        $css = $this->client->get($href)->getBody()->getContents();

        $style = "<style>" . $css . "</style>";

        $html = $crawler->outerHtml();
        $link = $link->outerHtml();
        $body = $crawler->filter('body')->html();

        $html = Str::replace($link, $style, $html);
        $html = Str::replace($body, '', $html);

        return Str::replace('</body></html>', '', $html);
    }

    public function buildPdf()
    {
        /**
         * div.h5_mainbody {
        min-height: 1169px;
        margin: auto;
        page-break-inside: avoid;
        }
        div.h5_mainbody_bg {
        min-height: 1169px;
        margin: auto;
        page-break-inside: avoid;
        }
        div.background-img-center {
        max-height: 1169px;
        background-repeat: no-repeat;
        background-size: cover;
        background-position: center center;
        }
         */

//        $exec = <<<EXEC
//wkhtmltopdf --footer-center 第[page]页 cover storage/app/iReader/$this->bookId/content/001.html toc storage/app/iReader/$this->bookId.html storage/app/iReader/$this->bookId.pdf
//EXEC;
//        echo $exec;

        $files = Storage::files("iReader/$this->bookId/content");

        foreach ($files as $file) {
            exec("wkhtmltopdf storage/app/$file storage/app/" . explode('.', $file)[0] . '.pdf');
        }

                $exec = <<<EXEC
wkhtmltopdf --footer-center 第[page]页 cover storage/app/iReader/$this->bookId/content/001.html toc storage/app/iReader/$this->bookId.html storage/app/iReader/$this->bookId.pdf
EXEC;
        echo $exec;
    }
}
