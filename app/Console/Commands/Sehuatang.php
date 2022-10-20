<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;
use PHPUnit\Exception;
use Symfony\Component\DomCrawler\Crawler;

class Sehuatang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sht:wm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '无码';

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
     * @return mixed
     */
    public function handle()
    {
        $jar = new \GuzzleHttp\Cookie\CookieJar();
        $client = new Client([
            'base_uri' => 'https://rewrew.kuto3.com',
            'timeout' => 5.0,
            'cookies' => $jar,
            'allow_redirects' => [
                'max' => 5,
                'strict' => false,
                'referer' => true,
                'protocols' => ['http', 'https'],
                'track_redirects' => true
            ]
        ]);

        $arrContextOptions = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        foreach (range(1, 351) as $page) {
            $response = $client->get('/forum-103-' . $page . '.html');

            $html = $response->getBody()->getContents();

            $crawler = new Crawler($html);

            $list = $crawler->filterXPath('//tbody[contains(@id, "normalthread_")]')->evaluate('substring-after(@id, "_")');

            foreach ($list as $tid) {
                $tries = 3;

                retry:
                try {
                    $response = $client->get('/thread-' . $tid . '-1-1.html');
                    $html = $response->getBody()->getContents();
                    $crawler = new Crawler($html);
                    $images = $crawler->filter('[onclick="zoom(this, this.src, 0, 0, 0)"]');

                    if ($images->count() > 0) {
                        $path = 'storage/av/' . $tid;
                        if (!is_dir($path)) {
                            mkdir($path);
                        }

                        $images->each(function (Crawler $image) use ($path, $arrContextOptions) {
                            $url = $image->attr('file');
                            $this->info($url);
                            $image_file_name = $path . '/' . basename($url);
                            if (!file_exists($image_file_name)) file_put_contents($image_file_name, file_get_contents(trim($url), false, $arrContextOptions));
                        });

                        $torrent = $crawler->filter('.attnm')->children()->first()->attr('href');

                        $response = $client->get($torrent);

                        $torrent_file_name = $path . '/' . str_replace(['attachment; filename=', '"'], '', $response->getHeader('Content-Disposition')[0]);

                        file_put_contents($torrent_file_name, $client->get($torrent)->getBody());
                    }

                    $this->info($page . "\t" . $tid);
                } catch (Exception $exception) {
                    $this->info($exception->getMessage());
                    if ($tries--) goto retry;
                    throw $exception;
                }

            }
        }
        return;
    }
}
