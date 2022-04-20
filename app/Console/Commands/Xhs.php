<?php

namespace App\Console\Commands;

use App\Models\XhsComment;
use App\Models\XhsImage;
use App\Models\XhsNote;
use App\Models\XhsVideo;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\VarDumper\VarDumper;

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

    protected $cookies;

    protected $cookieKey = 'xhs_cookie_store';

    protected $refreshCookie = false;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $cookies = CookieJar::fromArray(json_decode(Cache::get($this->cookieKey, '{}'), true), '.xiaohongshu.com');

        $this->http = new Client([
            'debug' => false,
            'cookies' => $cookies,
            'allow_redirects' => false,
            'headers' => [
                'accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.9',
                'accept-encoding' => 'gzip, deflate, br',
                'accept-language' => 'zh',
                'cache-control' => 'max-age=0',
                'dnt' => '1',
                'sec-ch-ua' => '" Not A;Brand";v="99", "Chromium";v="100", "Google Chrome";v="100"',
                'sec-ch-ua-mobile' => '?0',
                'sec-ch-ua-platform' => 'Windows',
                'sec-fetch-dest' => 'document',
                'sec-fetch-mode' => 'navigate',
                'sec-fetch-site' => 'cross-site',
                'sec-fetch-user' => '?1',
                'upgrade-insecure-requests' => 1,
                'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.4896.75 Safari/537.36'
            ],
            'on_headers' => function (ResponseInterface $response) {
                $cookies = $response->getHeader('Set-Cookie');
                $this->cookieHandle($cookies);
                $this->refreshCookie = !$this->refreshCookie;
                if ($this->refreshCookie) $this->refreshCookies();
            }
        ]);
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
            Log::info($note->time . "\t" . $note->title);
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
        $html = $this->request("https://www.xiaohongshu.com/user/profile/{$id}");

        $notes = json_decode(Str::between($html, '"notesDetail":', ',"albumDetail":'), true);

        return array_column($notes, 'id');
    }

    public function request($url)
    {
        $response = $this->http->get($url);

        return $response->getBody()->getContents();
    }

    /**
     * 刷新 Cookie
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function refreshCookies()
    {
        $this->http->post('https://www.xiaohongshu.com/fe_api/burdock/v2/shield/registerCanvas?p=cc', [
            'json' => [
                "id" => "e62e68aefbf29dc6c0e8df9425b41a40",
                "sign" => "713c02f1ffc1412d6a76c53e3a5eca9ce72ac06f4c4c880944f40e7c7e5631324e9f88c4272ddb83d9db22766c109e04c43bd6f2b66f44482334e09635c611160d3501378932bbdd30b3734e6abc91d01fc36e1ee35d4b4443f70067600345263e8f07d9afc8d8c5e1c2c624aa18399602af52d4de73a1afc3b8b01ded218f70cedcf093e45232411c841b9532f8fc8d50b450237edc56ff72ff421be662b87a1a6caaa96eb8a0944241bdab8d90864cc98e27732debb6823ae6e246a0c87b6bff79b0daf7232964911adbb01b2f15287fb98c4b94da3bd17f3c8c2d48b723edb0cc867261dbf3e3671ff5791c262cdbda03c0f4b1204353c09d3c2d29c1d32cee2e52dae878e4832a06d43b574c13eabe0951e87722c6b5e770187ad789a5f6903abdf8aba1ce2285ff6ce654c25b9f793d41acff5231ff7dac3a5e5ebe93e22ebf5016e2950f16579888809cf10b0a3c9d3d8632a1e8705704b9e547d239e34a58144075bd1074c46f5c7d30b86d1583f75c67b188d326c0304f1aef528c01d377cbb6d0a2bbbabb765da5d58d6f373e4348eff30efc00e4418c391f4d239a66640b48172c8e5a4a3409d15a4b7407977c5826e69bed3fad9db0d6c0a77ef706c51db8b1c62db1cbe2d2bfae51358fb5bba64ee9e14bed6a583396b471596d2707d824e9a553f3b29540815e89694a06fb1d84d7a28b1a9513d86486107e07fe6b97ac69ed1d605f51862f3378e9421938f01540abae5eecafc4dcda80ae4a45226b3ebe613a251d2b4432e63de0694b1f52f4426bb8236f609b38015c5929a7ff83d3dfd73eb5455105b3413b8c3b8abf8aec0f3f925b1244807b76d4eb91c7fdcf5f3ad29449abf9cdf70eac6746a1d760e2a02a6f0ea7405635a46ed9410bff2ded0db25dd6c5ce7926071e016646a858678e78f52413ae82c01a10f5b5a70c713d200235c43b9740be541dae8b9b442f9d8c9d3019af2e97e5b3a06f477408f749b4d5ea3cb78ec645f71c7b5809ebfc84a817459aabe9e3bd8e828096c7839138e4332c7c43bcef7fadc2d7569145373c79ebfbaf65522eaea513b1b3fe047e764dfb30e1d39c50df99a5d56f1a40d6d90716d8bb597f22ee834c62cd5b481b85b5e2de3e60507996a908410d1adbc201d2f0088c1142e011d16b820aabf9cdf70eac6746a1d760e2a02a6f0ea7405635a46ed9410bff2ded0db25dd6c5ce7926071e016646a858678e78f52413ae82c01a10f5b5a70c713d200235c43b9740be541dae8b9b442f9d8c9d3019af2e97e5b3a06f477408f749b4d5ea3c599da06e3afd7eac4b6c6d8d54dff1eef428b15f8fac484333331cb1dbdda4818dc1d4544b429d2e060237546556837b17bec26f9e08fa6ec386d21a621bb5b97b51d63c1d3e7db74f76f08ede948450d6a3e94f2329203fb3a9e500c70c6d862bf7f86886e995aa15da9491605c225cf414522166c18d4e1b4246d47c8dd051a183ac4d5ac8ed998faa1fe7284b8d6dc7839138e4332c7c43bcef7fadc2d7569145373c79ebfbaf65522eaea513b1b3fe047e764dfb30e1d39c50df99a5d56f1a40d6d90716d8bb597f22ee834c62cd5b481b85b5e2de3e60507996a908410d7a00843b203b26b29d3823aeacb0ad1d18c3fd65d246c56210ec1ee2baa9e1f49fc81c575a3d3e0534c67b061846aeea9bc3694de3699fdf2ae8434fed58c2067767224830465328e79741ab83fa08cc2f2949dff2a243ed6bca9f8708cf1f749d1410c6ab2ea320f5c5f1f8af1e2930959377256265807d8cd0900876f1ed04c46f5c7d30b86d1583f75c67b188d326ff79b0daf7232964ac9d41cfaa6386045d4bd1babdfd4dcdff79b0daf7232964ac9d41cfaa63860452a37b353f2ff0174376827f110c92646cf6cf76a9c1efb35f5a163beaef3ab20d7a4d5b7b246d08baedcb9f5d3769b67482eb505ff8798152d189bc4590b3a40d2d01f68b4b1221229ba2fe9b4da0dbd2e2c429176996856dbc7357e50411bbe75e2bcf979bb8e93862496acb60cf991191d39902e9a9a3170f5e396950a377"
            ]
        ]);
    }

    /**
     * 自动刷新 Cookie
     *
     * @param $setCookies
     */
    protected function cookieHandle($setCookies)
    {
        $cookies = [];

        foreach ($setCookies as $setCookie) {
            list($k, $v) = explode('=', explode(';', $setCookie)[0]);
            $cookies[$k] = $v;
        }

        if ($cookies) {
            $cache = Cache::get($this->cookieKey) ?? '[]';
            $cache = json_decode($cache, true);

            try {
                $this->cookies = array_merge($cache, $cookies);
            } catch (\Exception $exception) {
                $this->error($exception->getMessage());
            }

            Cache::put($this->cookieKey, json_encode($this->cookies));
        }
    }
}
