<?php

namespace App\Console\Commands;

use App\Models\XhsComment;
use App\Models\XhsImage;
use App\Models\XhsNote;
use App\Models\XhsVideo;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Xhs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'xhs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '小红书通知';

    /**
     * @var Client
     */
    protected $http;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->http = new Client();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notes_id = $this->index('5d8b88a60000000001005ad3');

        foreach ($notes_id as $xid) {
            list($x_note, $x_comment) = $this->item($xid);

            $x_note['x_id'] = $xid;

            if (!$note = XhsNote::where(['x_id' => $xid])->first()) {
                $note = XhsNote::create($x_note);

                if ($x_note['imageList']) {
                    foreach ($x_note['imageList'] as $image) {
                        XhsImage::create(array_merge($image, ['xsh_note_id' => $note->id]));
                    }
                }

                if ($x_note['type'] == 'video') {
                    XhsVideo::create([
                        'xsh_note_id' => $note->id,
                        'x_id' => $x_note['video']['id'],
                        'height' => $x_note['video']['height'],
                        'width' => $x_note['video']['width'],
                        'url' => $x_note['video']['url'],
                    ]);
                }
            }

            if ($x_comment['comments']) {

                foreach ($x_comment['comments'] as $comment) {

                    if (!$db_comment = XhsComment::where(['x_id' => $comment['id']])->first()) {
                        $comment['parent_id'] = $note['id'];
                        $comment['x_id'] = $comment['id'];
                        if (!isset($comment['user']['nickname'])) {
                            $comment['nickname'] = 'MASTER';
                        } else {
                            $comment['nickname'] = $comment['user']['nickname'];
                            $comment['user_id'] = $comment['user']['id'];
                        }
                        $db_comment = XhsComment::create($comment);
                    }

                    if ($comment['subComments']) {
                        foreach ($comment['subComments'] as $subComment) {
                            if (!XhsComment::where(['x_id' => $comment['id']])->first()) {
                                $subComment['parent_id'] = $db_comment['id'];
                                $subComment['x_id'] = $subComment['id'];
                                $subComment['isSubComment'] = true;
                                if (!isset($subComment['user']['nickname'])) {
                                    $subComment['nickname'] = 'MASTER';
                                } else {
                                    $subComment['nickname'] = $subComment['user']['nickname'];
                                    $subComment['user_id'] = $subComment['user']['id'];
                                }
                                XhsComment::create($subComment);
                            }
                        }
                    }
                }
            }
            $this->info($note->time . "\t" . $note->title);
            sleep(10);
        }
        return 0;
    }

    /**
     * 笔记详情
     * @param $id
     * @return array
     */
    public function item($id)
    {
        $html = $this->request("https://www.xiaohongshu.com/discovery/item/$id");
        $comments = Str::between($html, '"commentInfo":', ',"noteInfo"');
        $note = Str::between($html, '"noteInfo":', ',"noteType"');

        return [
            json_decode($note, true),
            json_decode($comments, true)
        ];
    }

    /**
     * 指定用户主页的笔记ID
     * @param $id
     * @return array
     */
    public function index($id)
    {
        $html = $this->request("https://www.xiaohongshu.com/user/profile/$id");

        $notes = json_decode(Str::between($html, '"notesDetail":', ',"albumDetail":'), true);

        return array_column($notes, 'id');
    }

    public function request($url)
    {
        $response = $this->http->get($url, [
            'headers' => [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'accept-encoding' => 'gzip, deflate, br',
                'accept-language' => 'zh,zh-CN;q=0.9,en-US;q=0.8,en;q=0.7,zh-TW;q=0.6,ja;q=0.5',
                'cache-control' => 'max-age=0',
                'cookie' => 'xhsTrackerId=5e83ce45-e2c2-4fde-cf73-6df327569537; xhsTracker=url=index&searchengine=baidu; timestamp2=2022040513bba4dd1e5f83d00fc61b22; timestamp2.sig=RCRVhhdIcJlGo8TawNyOJCyRb6BaoOxCssR74Rcn5g0; extra_exp_ids=supervision_exp,supervision_v2_exp,commentshow_clt1,gif_exp1,ques_clt1',
                'dnt' => '1',
                'sec-ch-ua' => '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => '"Windows"',
                'sec-fetch-dest' => 'document',
                'sec-fetch-mode' => 'navigate',
                'sec-fetch-site' => 'none',
                'sec-fetch-user' => '?1',
                'upgrade-insecure-requests' => 1,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.75 Safari/537.36'
            ]
        ]);

        return $response->getBody()->getContents();
    }
}
