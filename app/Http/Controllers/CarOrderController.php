<?php

namespace App\Http\Controllers;

use App\Dao\UsersRecordDao;
use App\Dao\Wechat\TemplateMessageDao;
use App\Extend\DateTimeTool;
use App\Http\Controllers\Store\BaseController;
use App\Models\CarOrderFieldValue;
use App\Models\Store;
use App\Models\StoreCard;
use App\Models\StoreCardProductSet;
use App\Models\StoreCarIssue;
use App\Models\StoreCarOrder;
use App\Models\StoreCarOrderMoneyStaff;
use App\Models\StoreCarOrderProduct;
use App\Models\StoreCarOrderProductStaff;
use App\Models\StoreCarOrderStaff;
use App\Models\StoreCoupon;
use App\Models\StoreCouponProduct;
use App\Models\StoreFullMinus;
use App\Models\StoreJob;
use App\Models\StoreProduct;
use App\Models\StoreStaff;
use App\Models\StoreStaffCommission;
use App\Models\StoreStaffCommissionLadder;
use App\Models\StoreStaffCommissionSet;
use App\Models\StoreStaffProductSet;
use App\Models\Users;
use App\Models\UsersCard;
use App\Models\UsersCollection;
use App\Models\UsersCoupon;
use App\Models\UsersField;
use App\Models\UsersLevel;
use App\Models\UsersLevelProductSet;
use App\Models\UsersLog;
use App\Models\UsersProduct;
use App\Models\UsersRecord;
use App\Util\Tools;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use function App\Http\Controllers\Store\failed;
use function App\Http\Controllers\Store\success;

class CarOrderController extends BaseController
{
    /**
     * 列表
     *
     * @param Request $request
     * @return array
     */
    public function index(Request $request): array
    {
        $status = $request->input('status', '');

        list($start_at, $end_at) = DateTimeTool::getStartEnd(
            request()->post('date_type'),
            request()->post('start_time'),
            request()->post('end_time'),
        );

        $list = StoreCarOrder::with('user')
            ->when(strlen($status), function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->where('store_id', $this->store_id)
            ->when($request->input('q'), function ($query, $keyword) {
                $users_id = Users::where('name', 'like', "%{$keyword}%")
                    ->orWhere('mobile', 'like', "%{$keyword}%")
                    ->orWhere('car_numbers', 'like', "%{$keyword}%")
                    ->orWhere('car_number', 'like', "%{$keyword}%")
                    ->pluck('id');
                return $query->where(function ($query) use ($users_id, $keyword) {
                    $query->whereIn('user_id', $users_id)
                        ->orWhere('car_number', 'like', "%$keyword%");
                });
            })
            ->when($request->input('car_number', false), function ($query, $car_number) {
                return $query->where('car_number', $car_number);
            })
            ->when($start_at, function ($query, $start_at) {
                $query->where('received_at', '>=', date('Y-m-d H:i:s', $start_at));
            })
            ->when($end_at, function ($query, $end_at) {
                $query->where('received_at', '<', date('Y-m-d H:i:s', $end_at));
            })
            ->orderBy('received_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('limit', 15));

        return success(Tools::getPageData($list));
    }

    /**
     * 详情
     *
     * @param Request $request
     * @return array
     */
    public function detail(Request $request): array
    {
        $id = $request->input('id');

        $order = StoreCarOrder::find($id);
        $order->finished_at = $order->finished_at ?? '';
        /**
         * 店铺满减
         */
        $order->full_minius_info = StoreFullMinus::where("id", $order->full_minus_id)->withoutGlobalScope('delete')->first();

        /**
         * 满减券
         */
        $order->coupon_full_minus = UsersCoupon::with(["coupon"])->withoutGlobalScope('delete')->find($order->coupon_full_minus_id);

        /**
         * 折扣券
         */
        $order->coupon_discount = UsersCoupon::with(["coupon"])->withoutGlobalScope('delete')->find($order->coupon_discount_id);

        if (in_array($order->status, [StoreCarOrder::STATUS_RECEIVE, StoreCarOrder::STATUS_PAYMENT])) {
            $with = [
                'user',
                'staffs:id,staff_id,order_id,is_zd',
                'issues',
                'products:id,order_id,product_id,number,use_card_id,use_card_type,use_coupon_id,use_coupon_type,pay_price,price,card_surplus_number',
                'money_staffs',
                'money_use_card'
            ];
        } else {
            $with = [
                'user',
                'staffs:id,staff_id,order_id,is_zd',
                'issues',
                'products:id,order_id,product_id,number,use_card_id,use_card_type,use_coupon_id,use_coupon_type,pay_price,price,card_surplus_number',
                'money_staffs',
                'products.card',
                'products.coupon',
                'operator:id,name,username,is_admin',
                'money_use_card'
            ];
        }

        $order->load($with);

        if (empty($order) or $this->store_id != $order->store_id) return failed('订单不存在');

        if (empty($order->user)) return failed('用户不存在或已被删除');
        if (empty($order->products)) return failed('员工不存在或已被删除');

        $order->field = DB::table('users_field as f')
            ->where('f.store_id', $this->store_id)
            ->where('f.is_delete', 0)
            ->where('f.type', UsersField::TYPE_CAR)
            ->leftJoin('car_order_field_value as v', function ($join) use ($order) {
                $join->on('f.id', '=', 'v.field_id')->where('v.order_id', '=', $order->id);
            })->orderBy('f.id', 'asc')
            ->select("f.id", "f.store_id", "f.name", "v.value", "v.id as value_id")
            ->get();

        $record_id = last(explode(',', $order->payment_record_id));
        $order->pay_method = UsersCollection::where("record_id", $record_id)->withoutGlobalScope("delete")->first();
        $order->zk_card_res = json_decode($order->zk_card_res);
        return success($order);
    }

    /**
     * 订单完工
     *
     * @param Request $request
     * @return array
     */
    public function complete(Request $request): array
    {
        $id = $request->input('id');
        $order = StoreCarOrder::where('store_id', $this->store_id)->find($id);
        if ($order) {
            if ($order->status == StoreCarOrder::STATUS_RECEIVE) {
                $order->status = StoreCarOrder::STATUS_PAYMENT;
                $order->save();

                $user = Users::find($order->user_id);

                if ($user && $user->openid) {
                    $store = Store::find($order->store_id);
                    $message_data = [
                        'touser' => $user->openid,
                        'template_id' => '8iSIc2LfEhrvjsY_3Hey6fOLR98KdL2y-ZTv52FL5nk',
                        'url' => "",
                        'data' => [
                            'first' => '您好，您的服务已完成!',
                            'keyword1' => "汽车服务",
                            'keyword2' => date('Y-m-d H:i:s'),
                            'remark' => $store->nickname,
                        ],
                    ];
                    TemplateMessageDao::send($message_data);
                }
            }
            return success();
        }

        return failed('状态异常，请刷新后重试');
    }

    /**
     * 创建 Issue
     *
     * @param Request $request
     * @return array
     */
    public function createIssue(Request $request): array
    {
        $issue = StoreCarIssue::create([
            'store_id' => $this->store_id,
            'order_id' => $request->input('order_id'),
            'issue' => $request->input('issue'),
            'images' => json_decode($request->input('images', []))
        ]);

        $user = Users::find($issue->order->user_id);

        if ($user && $user->openid) {
            $store = Store::find($issue->store_id);
            $message_data = [
                'touser' => $user->openid,
                'template_id' => '7c4nyBvSAhya_gP5FWTGDaiZpJ7zuXmFfIa9-R5_11g',
                'url' => "",
                'data' => [
                    'first' => '您好，报告如下：',
                    'keyword1' => "新发现",
                    'keyword2' => $store->nickname,
                    'keyword3' => $issue->issue,
                    'keyword4' => date('Y-m-d H:i:s'),
                ],
            ];
            TemplateMessageDao::send($message_data);
        }

        return success($issue);
    }

    /**
     * 创建
     *
     * @param Request $request
     * @return array
     */
    public function create(Request $request): array
    {
        $id = $request->input('id', null);

        $user_id = $request->input('user_id');

        $car_number = $request->input('car_number');

        $received_at = $request->input('received_at');
        $finished_at = $request->input('finished_at');

        $money = json_decode($request->input('money', '[]'), true);

        $money_staff = empty($money) ? [] : $money['staffs'];

        $staffs = json_decode($request->input('staffs', '[]'), true);

        $remark = $request->post('remark', '');

        $products = json_decode($request->input('products', '[]'), true);

        $fields = json_decode($request->input('field', '[]'), true);

        $images = json_decode($request->input('images', '[]'));

        try {
            DB::beginTransaction();

            $order = StoreCarOrder::updateOrCreate(['id' => $id], [
                'store_id' => $this->store_id,
                'type' => 0,
                'money' => empty($money) ? 0 : $money['price'],
                'user_id' => $user_id,
                'car_number' => $car_number,
                'received_at' => $received_at,
                'finished_at' => $finished_at,
                'remark' => $remark ?? '',
                'images' => $images,
                'status' => StoreCarOrder::STATUS_RECEIVE,
                'custom' => $request->input('custom', '') ?? '',
            ]);

            foreach ($fields as $field) {
                if (isset($field["id"]) && isset($field["value"])) {
                    CarOrderFieldValue::updateOrCreate([
                        "order_id" => $order->id,
                        "field_id" => $field["id"],
                    ], [
                        "value" => $field["value"]
                    ]);
                }
            }

            StoreCarOrderProduct::where('order_id', $order->id)->update(['is_delete' => 1]);
            StoreCarOrderStaff::where('order_id', $order->id)->update(['is_delete' => 1]);
            StoreCarOrderProductStaff::where('order_id', $order->id)->update(['is_delete' => 1]);
            StoreCarOrderMoneyStaff::where('order_id', $order->id)->update(['is_delete' => 1]);

            $new_product_staff = [];
            $staffs_id = [];
            foreach ($staffs as $staff) {
                $new_product_staff[] = ['order_id' => $order->id, 'staff_id' => $staff['id'], 'is_zd' => $staff['is_zd']];
                $staffs_id[] = $staff['id'];
            }
            if ($new_product_staff) StoreCarOrderStaff::insert($new_product_staff);

            /**
             * 写入商品
             */
            foreach ($products as $product) {
                $_product = StoreProduct::find($product['id']);
                $OrderProduct = StoreCarOrderProduct::create([
                    'order_id' => $order->id,
                    'product_id' => $product['id'],
                    'number' => $product['number'],
                    'price' => $_product['price']
                ]);

                foreach ($product['staffs'] as $staff) {
                    StoreCarOrderProductStaff::create([
                        'order_product_id' => $OrderProduct->id,
                        'product_id' => $OrderProduct->product_id,
                        'staff_id' => $staff['id'],
                        'order_id' => $order->id,
                        'is_zd' => $staff['is_zd']
                    ]);
                    $staffs_id[] = $staff['id'];
                }
            }
            /**
             * 手动输入金额的服务人员
             */
            foreach ($money_staff as $m_staff) {
                $staffs_id[] = $m_staff['id'];
                StoreCarOrderMoneyStaff::create([
                    'staff_id' => $m_staff['id'],
                    'order_id' => $order->id,
                    'is_zd' => $m_staff['is_zd']
                ]);
            }

            if (!$id && $staffs_id) { // 创建 发送派工通知
                foreach (array_unique($staffs_id) as $staff_id) {
                    $staff = StoreStaff::find($staff_id);
                    if ($staff && $staff->openid) {
                        $store = Store::find($order->store_id);
                        $message_data = [
                            'touser' => $staff->openid,
                            'template_id' => 'WF8-OWy7D4D8qiZBq-_gPOLtc16pRdGeQ3JflCz_a8A',
                            'url' => "",
                            'data' => [
                                'first' => '您有新的派工',
                                'keyword1' => "服务派工",
                                'keyword2' => $store->region . $store->address,
                                'keyword3' => $store->nickname,
                                'keyword4' => $store->contacts_mobile,
                            ],
                        ];
                        TemplateMessageDao::send($message_data);
                    }
                }
            }

            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return [$exception->getMessage()];
        }

        return success($order);
    }

    /**
     * 问题列表
     *
     * @param Request $request
     * @return array
     */
    public function getIssues(Request $request): array
    {
        return success(
            Tools::getPageData(
                StoreCarIssue::where('store_id', $this->store_id)
                    ->where('order_id', $request->input('order_id', 0))
                    ->paginate($request->input('limit', 15))
            )
        );
    }

    /**
     * 问题详情
     *
     * @param Request $request
     * @return array
     */
    public function getIssueDetail(Request $request): array
    {
        if ($issue = StoreCarIssue::where('store_id', $this->store_id)->find($request->input('id', 0)))
            return success($issue);
        return failed('问题未找到');
    }

    /**
     * 计算提成
     *
     * @param Request $request
     * @return array
     * @throws \Throwable
     */
    public function commission(Request $request): array
    {
        $order_id = $request->post('id');
        $secend = $request->post('secend') ?? 1;

        //总金额
        $total = 0;

        //实际支付的钱数
        $real_collect = 0;

        //特价全特价的金额
        $bargain_price_money = 0;

        //当前时间
        $time = time();
        //房台消费记录

        $order = StoreCarOrder::where('store_id', $this->store_id)
            ->find($order_id);

        if (!$order) return failed('订单未找到');

        $users_id = $order->user_id;

        $new_products = [];

        /**
         * 手动输入金额
         */
        $input_money = json_decode($request->post('money', '[]'), true);
        if (empty($input_money)) {
            $input_money['price'] = $order->money;
            $input_money['old_price'] = $order->money;
            $input_money['staffs'] = [];
            foreach ($order->money_staffs as $money_staff) {
                $input_money['staffs'][] = ['id' => $money_staff['staff_id'], 'is_zd' => $money_staff['is_zd']];
            }
        }
        $total = $input_money['old_price'];

        /**
         * 选择项目商品
         */
        $post_product = true;
        if (!$products = json_decode($request->input('products', '[]'), true)) {
            $products = $order->products->toArray();
            $post_product = false;
        }

        foreach ($products as $product) {
            $_staffs = [];
            foreach ($product['staffs'] as $staff) {
                $_staffs[] = ['id' => $staff['staff_id'], 'is_zd' => $staff['is_zd']];
            }
            $one = [];
            $one['id'] = $product['id'] ?? 0;
            $one['product_id'] = $product['product_id'];
            $one['use_card'] = [];
            $one['coupon_gift_id'] = $product['coupon_gift_id'] ?? 0;
            $one['coupon_bargain_price_id'] = $product['coupon_bargain_price_id'] ?? 0;
            $one['number'] = $product['number'] ?? 1;
            $one['price'] = $post_product ? $product['price'] : $product['product']['price'];
            $one['staffs'] = $_staffs;
            $new_products[] = $one;
        }
        try {
            $data = [];
            //先查时长卡
            $card3 = UsersCard::where("card_type", 3)->where("end_time", ">", $time)->where("users_id", $users_id)->orderBy("number", "desc")->with(['products', 'card'])->get();
            foreach ($card3 as $card) {
                foreach ($card->products as $one) {
                    $one->new_number = $one->number;
                }
            }
            //次卡
            $card1 = UsersCard::where("card_type", 1)->where("card_id", 1)->where("users_id", $users_id)->with(['products'])->get();
            foreach ($card1 as $card) {
                foreach ($card->products as $one) {
                    $one->new_number = $one->number;
                }
            }
            //折扣卡
            $zk_cards = UsersCard::where("card_type", 4)->where("users_id", $users_id)->where("use_money", ">", 0)->with(['card'])->get();
            foreach ($zk_cards as $card) $card->new_use_money = $card->use_money;

            //储值卡字段在这里
            $users = Users::find($users_id);
            if (empty($users)) throw new \Exception("用户未找到");
            $users->new_money = $users->money;
            $users_level = UsersLevel::withoutGlobalScope("delete")->find($users->level_id);

            //优惠卷，可用礼品卷
            $users_coupon = UsersCoupon::getCanUse($users_id);
            $coupon_full_minus = [];
            $coupon_gift = [];
            $coupon_discount = [];
            $coupon_bargain_price = [];
            foreach ($users_coupon as $uc) {
                $uc = $uc->toArray();
                if ($uc['type'] == StoreCoupon::full_minus) {//满减券
                    $coupon_full_minus[] = $uc;
                } elseif ($uc['type'] == StoreCoupon::gift) {//礼品券
                    $coupon_gift[] = $uc;
                } elseif ($uc['type'] == StoreCoupon::discount) {//折扣券
                    $coupon_discount[] = $uc;
                } elseif ($uc['type'] == StoreCoupon::bargain_price) {//特价券
                    $coupon_bargain_price[] = $uc;
                }
            }

            $commissionSet = StoreStaffCommissionSet::whereIn("store_id", $this->getSyncStoreId(Store::SYNC_TYPE_COMMISSION))->first();

            foreach ($new_products as &$row) {
                $store_product = StoreProduct::withoutGlobalScope("delete")->find($row['product_id']);
                $total += $store_product['price'];
                if (empty($store_product)) throw new \Exception("商品未找到");
                $row['old_price'] = $store_product->price;
                $row['cost'] = $store_product->cost;
                $row['use_card'] = "";
                $row['product'] = $store_product;
                $row_price = $row['price'];

                //时长卡
                foreach ($card3 as $sc_card) {
                    foreach ($sc_card->products as $card_product) {
                        if ($row['product_id'] == $card_product->product_id) {

                            if ($card_product->number_type == 1 && $card_product->new_number > 0) {
                                $card_product->new_number = $card_product->new_number - 1;
                                $row['use_card'] = $sc_card;
                            } elseif ($card_product->number_type == 2) {
                                $row['use_card'] = $sc_card;
                            }
                            foreach ($row['staffs'] as &$staff) {
                                $staff_info = StoreStaff::withoutGlobalScope("delete")
                                    ->select(["job_id", "avatar", "name", "id"])
                                    ->find($staff['id']);

                                $staff_info['job'] = [];
                                $staff_info['radio'] = 0;
                                $staff_info['money'] = 0;

                                if (!empty($staff_info->job_id)) {
                                    $staff_info['job'] = StoreJob::find($staff_info->job_id);
                                    $product_set = StoreStaffProductSet::where("store_id", $this->store_id)
                                        ->where("job_id", $staff_info->job_id)
                                        ->where("product_id", $row['product_id'])->first();

                                    if (!empty($product_set)) {
                                        $ladder_info = null;
                                        if (!empty($commissionSet)) {
                                            if ($commissionSet->royalty_method_cck == 1) { // 按实收实扣
                                                $up = UsersProduct::where("users_id", $users_id)
                                                    ->where('product_id', $store_product->id)
                                                    ->where('number', '>', 0)
                                                    ->where('card_id', '<>', 1)
                                                    ->orderBy('id', 'asc')
                                                    ->first();
                                                if ($up && $up->real_collect > 0) $store_product->price = $up->real_collect;
                                            }

                                            $scope = 'all';
                                            if ($commissionSet->ladder_type != 1) $scope = $store_product->type == 1 ? 'goods' : 'services';
                                            $ladder_info = StoreStaffCommissionLadder::getLadderInfo($this->store_id, $staff_info['id'], $scope);
                                        }

                                        list($staff_info['achievement'], $staff_info['money']) = StoreStaffCommission::getCommissionMoney(
                                            $product_set,
                                            $store_product,
                                            $row['number'],
                                            $staff['is_zd'],
                                            $ladder_info,
                                            count($row['staffs']),
                                            false
                                        );
                                    }
                                }
                                $staff['commission'] = $staff_info;
                            }
                            break 2;
                        }
                    }
                }

                //次卡
                if (empty($row['use_card'])) {
                    foreach ($card1 as $card) {
                        foreach ($card->products as $one) {
                            if ($row['product_id'] == $one->product_id) {
                                if ($one->new_number > 0) {
                                    $one->new_number = $one->new_number - 1;
                                    $row['use_card'] = $card;
                                    foreach ($row['staffs'] as &$staff) {
                                        $staff_info = StoreStaff::withoutGlobalScope("delete")->select(["job_id", "avatar", "name", "id"])->find($staff['id']);
                                        if (!empty($staff_info->job_id)) {
                                            $staff_info['job'] = StoreJob::find($staff_info->job_id);
                                            $product_set = StoreStaffProductSet::where("store_id", $this->store_id)->where("job_id", $staff_info->job_id)->where("product_id", $row['product_id'])->first();
                                            if (!empty($product_set)) {
                                                $ladder_info = null;
                                                if (!empty($commissionSet)) {
                                                    if ($commissionSet->royalty_method_cck == 1) { // 按实收实扣
                                                        $up = UsersProduct::where("users_id", $users_id)
                                                            ->where('product_id', $store_product->id)
                                                            ->where('number', '>', 0)
                                                            ->where('card_id', '<>', 1)
                                                            ->orderBy('id', 'asc')
                                                            ->first();
                                                        if ($up && $up->real_collect > 0) $store_product->price = $up->real_collect;
                                                    }

                                                    $scope = 'all';
                                                    if ($commissionSet->ladder_type != 1) $scope = $store_product->type == 1 ? 'goods' : 'services';
                                                    $ladder_info = StoreStaffCommissionLadder::getLadderInfo($this->store_id, $staff_info['id'], $scope);
                                                }

                                                list($staff_info['achievement'], $staff_info['money']) = StoreStaffCommission::getCommissionMoney(
                                                    $product_set,
                                                    $store_product,
                                                    $row['number'],
                                                    $staff['is_zd'],
                                                    $ladder_info,
                                                    count($row['staffs']),
                                                    false
                                                );
                                            } else {
                                                $staff_info['radio'] = 0;
                                                $staff_info['money'] = 0;
                                            }
                                        } else {
                                            $staff_info['job'] = [];
                                            $staff_info['radio'] = 0;
                                            $staff_info['money'] = 0;
                                        }
                                        $staff['commission'] = $staff_info;
                                    }
                                }
                                break 2;
                            }
                        }
                    }
                }

                //折扣卡
                if (empty($row['use_card'])) {
                    foreach ($zk_cards as $card) {
                        $store_product->price = $store_product->vip_price ?: $store_product->getOriginal('price');
                        list($row['card_discount'], $row['price'], $row['card_discount_type']) = StoreCardProductSet::cardDiscount($store_product, $card);
                        if ($row['price'] <= $card->new_use_money) {
                            $card->new_use_money = $card->new_use_money - $row['price'];
                            $row['use_card'] = $card;
                            foreach ($row['staffs'] as &$staff) {
                                $staff_info = StoreStaff::withoutGlobalScope("delete")->select(["job_id", "avatar", "name", "id"])->find($staff['id']);
                                $staff_info['job'] = [];
                                $staff_info['radio'] = 0;
                                $staff_info['money'] = 0;
                                if (!empty($staff_info->job_id)) {
                                    $staff_info['job'] = StoreJob::find($staff_info->job_id);
                                    $product_set = StoreStaffProductSet::where("store_id", $this->store_id)->where("job_id", $staff_info->job_id)->where("product_id", $store_product->id)->first();

                                    if (!empty($product_set)) {
                                        $store_product->price_two = $store_product->cost;
                                        $ladder_info = null;
                                        if (!empty($commissionSet)) {
                                            if ($commissionSet->royalty_method == 2) { // 按实收实扣
                                                $store_product->price = $row['price'];
                                            }

                                            $scope = 'all';
                                            if ($commissionSet->ladder_type != 1) $scope = $store_product->type == 1 ? 'goods' : 'services';
                                            $ladder_info = StoreStaffCommissionLadder::getLadderInfo($this->store_id, $staff_info['id'], $scope);
                                        }

                                        list($staff_info['achievement'], $staff_info['money']) = StoreStaffCommission::getCommissionMoney(
                                            $product_set,
                                            $store_product,
                                            $row['number'],
                                            $staff['is_zd'],
                                            $ladder_info,
                                            count($row['staffs']),
                                            false
                                        );
                                    }
                                }
                                $staff['commission'] = $staff_info;
                            }
                            break;
                        }
                    }
                }

                //储值卡
                if (empty($row['use_card'])) {
                    $row['price'] = $store_product->price = $store_product->vip_price ?: $store_product->getOriginal('price');
                    if (!empty($users_level)) {
                        list($row['level_radio_price'], $row['price'], $row['level_discount_type'], $row['ratio']) = UsersLevelProductSet::levelDiscount($users_level, $row['product_id'], $store_product->type, $store_product->price);
                    }
                    if ($row['price'] <= $users->new_money && $users->new_money != 0) {
                        $users->new_money = $users->new_money - $row['price'];
                        $users->card_type = 2;
                        $row['use_card'] = $users;
                        foreach ($row['staffs'] as &$staff) {
                            $staff_info = StoreStaff::withoutGlobalScope("delete")->select(["job_id", "avatar", "name", "id"])->find($staff['id']);
                            $staff_info['job'] = [];
                            $staff_info['radio'] = 0;
                            $staff_info['money'] = 0;

                            if (!empty($staff_info->job_id)) {
                                $staff_info['job'] = StoreJob::find($staff_info->job_id);
                                $product_set = StoreStaffProductSet::where("store_id", $this->store_id)->where("job_id", $staff_info->job_id)->where("product_id", $store_product->id)->first();
                                if (!empty($product_set)) {
                                    $store_product->price_two = $store_product->cost;
                                    $ladder_info = null;
                                    if (!empty($commissionSet)) {
                                        if ($commissionSet->royalty_method == 2) {
                                            $store_product->price = $row['price'];
                                        }
                                        if ($commissionSet->royalty_method_cck == 1) { // 按实收实扣
                                            $staff_info['czk_bl'] = bcdiv($users->real_money, $users->money, 5);
                                            $staff_info['czk_yj'] = $row['price'];
                                            $scale = 2;
                                            $staff_info['czk_achievement'] = $row['price'];
                                            $store_product->price = $staff_info['czk_sc_achievement'] = round(bcmul($staff_info['czk_achievement'], $staff_info['czk_bl'], $scale), 2); // 储值卡实收实扣业绩
                                        }

                                        $scope = 'all';
                                        if ($commissionSet->ladder_type != 1) $scope = $store_product->type == 1 ? 'goods' : 'services';
                                        $ladder_info = StoreStaffCommissionLadder::getLadderInfo($this->store_id, $staff_info['id'], $scope);
                                    }

                                    list($staff_info['achievement'], $staff_info['money']) = StoreStaffCommission::getCommissionMoney(
                                        $product_set,
                                        $store_product,
                                        $row['number'],
                                        $staff['is_zd'],
                                        $ladder_info,
                                        count($row['staffs']),
                                        false
                                    );
                                }
                            }
                            $staff['commission'] = $staff_info;
                        }
                    }
                }

                //多余的对比优惠券，单个商品用礼品券，特价券
                if (empty($row['use_card'])) {
                    //快速收银方式计算商品价格，员工提成等
                    $row['price'] = $row_price;
                    if (!empty($users_level) && $users_level->zk_switch == UsersLevel::OPEN) {
                        list($row['level_radio_price'], $row['price'], $row['level_discount_type'], $row['ratio']) = UsersLevelProductSet::levelDiscount($users_level, $row['product_id'], $store_product->type, $row['old_price']);
                        $row_price = $row['price'];
                    }

                    if (!empty($row['coupon_gift_id'])) {
                        $coupon_gift_id = $row['coupon_gift_id'];
                        $cgl = UsersCoupon::where("id", $coupon_gift_id)->where("status", UsersCoupon::not_used)->with(['coupon'])->first();

                        throw_if(empty($cgl), new \Exception('礼品券状态异常，请刷新后重试'));

                        $coupon_product = StoreCouponProduct::where("coupon_id", $cgl['coupon_id'])->with(["product"])->first();
                        if ($coupon_product && $row['product_id'] == $coupon_product->product_id) {
                            $row['coupon_gift'] = $cgl;
                            $row['use_coupon_id'] = $cgl->id;
                            $row['use_coupon_type'] = $cgl->coupon->type;
                            $row['use_coupon_name'] = $cgl->coupon->name;
                            $row['price'] = 0;
                        }
                    } else {
                        foreach ($coupon_gift as $kk => $cg) {
                            $coupon_product = StoreCouponProduct::where("coupon_id", $cg['coupon_id'])->with(["product"])->first();
                            if ($coupon_product && $row['product_id'] == $coupon_product->product_id) {
                                $row['coupon_gift'] = $cg;
                                $row['use_coupon_id'] = $cg['id'];
                                $row['use_coupon_type'] = $cg['type'];
                                $row['use_coupon_name'] = $cg['store_coupon_name'];
                                //去掉对应这一张，避免重复使用
                                unset($coupon_gift[$kk]);
                                $row['price'] = 0;
                                break;
                                //礼品券直接抵扣完，所以不用计入总价
                            }
                        }
                    }
                    //判断有没有特价券
                    if (empty($row['coupon_gift'])) {
                        if (!empty($row['coupon_bargain_price_id'])) {
                            $coupon_bargain_price_id = $row['coupon_bargain_price_id'];
                            $cgl = UsersCoupon::where("id", $coupon_bargain_price_id)->where("status", UsersCoupon::not_used)->with(['coupon'])->first();

                            throw_if(empty($cgl), new \Exception('礼品券状态异常，请刷新后重试'));
                            $coupon_product = StoreCouponProduct::where("coupon_id", $cgl['coupon_id'])->with(["product", "bargain"])->first();
                            if ($coupon_product && $row['product_id'] == $coupon_product->product_id) {
                                $row['coupon_bargain_price'] = $cgl;
                                $row['use_coupon_id'] = $cgl->id;
                                $row['use_coupon_type'] = $cgl->coupon->type;
                                $row['use_coupon_name'] = $cgl->coupon->name;
                                //总价格加上特价券价格
                                $real_collect += $coupon_product->bargain->bargain_price;
                                $row['price'] = $coupon_product->bargain->bargain_price;
                            }
                        } else {
                            foreach ($coupon_bargain_price as $kk => $cg) {
                                $coupon_product = StoreCouponProduct::where("coupon_id", $cg['coupon_id'])->with(["product", "bargain"])->first();
                                if ($coupon_product && $row['product_id'] == $coupon_product->product_id) {
                                    $row['coupon_bargain_price'] = $cg;
                                    $row['use_coupon_id'] = $cg['id'];
                                    $row['use_coupon_type'] = $cg['type'];
                                    $row['use_coupon_name'] = $cg['store_coupon_name'];
                                    //去掉对应这一张，避免重复使用
                                    unset($coupon_bargain_price[$kk]);
                                    //总价格加上特价券价格
                                    $real_collect += $coupon_product->bargain->bargain_price;
                                    $bargain_price_money += $coupon_product->bargain->bargain_price;
                                    $row['price'] = $coupon_product->bargain->bargain_price;
                                    break;
                                }
                            }
                        }
                    }
                    //如果卡片和礼品特价券都为空，那么这个商品价格就要都计入总价
                    if (empty($row['coupon_gift']) && empty($row['coupon_bargain_price']) && empty($row['coupon_gift_id']) && empty($row['coupon_bargain_price_id'])) {
                        $real_collect += $row['price'];
                    }

                    foreach ($row['staffs'] as &$staff) {
                        $staff_info = StoreStaff::withoutGlobalScope("delete")->select(["job_id", "avatar", "name", "id"])->find($staff['id']);
                        $staff_info['job'] = [];
                        $staff_info['radio'] = 0;
                        $staff_info['money'] = 0;
                        if (!empty($staff_info->job_id)) {
                            $staff_info['job'] = StoreJob::find($staff_info->job_id);
                            $product_set = StoreStaffProductSet::where("store_id", $this->store_id)->where("job_id", $staff_info->job_id)->where("product_id", $store_product->id)->first();

                            if (!empty($product_set)) {
                                $ladder_info = null;
                                if (!empty($commissionSet)) {
                                    if ($commissionSet->royalty_method == 2) { // 按实收实扣
                                        $store_product->price = $row_price;
                                    }
                                    $scope = 'all';
                                    if ($commissionSet->ladder_type != 1) $scope = $store_product->type == 1 ? 'goods' : 'services';
                                    $ladder_info = StoreStaffCommissionLadder::getLadderInfo($this->store_id, $staff_info['id'], $scope);
                                }

                                list($staff_info['achievement'], $staff_info['money']) = StoreStaffCommission::getCommissionMoney(
                                    $product_set,
                                    $store_product,
                                    $row['number'],
                                    $staff['is_zd'],
                                    $ladder_info,
                                    count($row['staffs']),
                                    $commissionSet->fkk_switch
                                );
                            }
                        }
                        $staff['commission'] = $staff_info;
                    }
                }
            }
            $money = new \stdClass();

            /**
             * 手动输入金额
             */
            $money->old_price = $input_money['old_price'];
            $money->price = $input_money['old_price'];
            $money->use_card = '';
            $sc_real_collect = $money->price;

            $use_card_type = '';

            //折扣卡
            if ($money->price > 0 && $zk_cards) {
                foreach ($zk_cards as $card) { // 折扣卡折扣
                    $card_info = StoreCard::withoutGlobalScope("delete")->find($card->card_id);
                    $money_zk_price = round($money->old_price * $card_info->discount / 100, 2);
                    if ($money_zk_price <= $card->new_use_money) {
                        $money->price = $money_zk_price;
                        $card->new_use_money = $card->new_use_money - $money->price;
                        $card->card_type = $card_info->type;
                        $money->use_card = $card;
                        $use_card_type = 'zkk';
                        $sc_real_collect = 0;
                        break;
                    }
                }
            }

            //储值卡
            if ($money->price > 0 && empty($money->use_card) && $users->money) {
                if ($users_level) { // 用户等级折扣
                    $sc_real_collect = $money->price = sprintf('%.2f', $money->old_price * $users_level->zd_radio / 100);
                    $money->zd_radio = $users_level->zd_radio;
                    $money->level_discount = sprintf('%.2f', $money->old_price - $real_collect);
                }
                if ($money->price <= $users->new_money) {
                    $users->new_money = $users->new_money - $money->price;
                    $users->card_type = 2;
                    $money->use_card = $users;
                    $sc_real_collect = 0;
                    $use_card_type = 'czk';
                }
            }
            $real_collect += $sc_real_collect;

            $money->staffs = [];
            foreach ($input_money['staffs'] as $_staff) {
                $_staff_info = StoreStaff::select(["job_id", "avatar", "name", "id"])->find($_staff['id']);
                if (empty($_staff_info)) throw new \Exception('服务人员未找到');
                $_staff_info['radio'] = 0;
                $_staff_info['money'] = 0;
                $_staff_info['achievement'] = 0;
                $_staff_info['job'] = StoreJob::find($_staff_info['job_id']);
                $price = $input_money['old_price'];
                $ladder_info = [];

                if (!empty($commissionSet)) {
                    if ($commissionSet->royalty_method == 2) {
                        $price = $money->price;
                    }

                    if ($use_card_type == 'czk' && $commissionSet->royalty_method_cck == 1) {
                        $_staff_info['czk_bl'] = bcdiv($users->real_money, $users->money, 5);

                        $scale = 2;
                        $_staff_info['czk_achievement'] = $money->price;
                        $price = $_staff_info['czk_sc_achievement'] = round(bcmul($_staff_info['czk_achievement'], $_staff_info['czk_bl'], $scale), 2); // 储值卡实收实扣业绩
                    }

                    $start = strtotime(date('Y-m-01'));
                    $end = strtotime('+1 month', $start) - 1;
                    if ($commissionSet->ladder_type == 1) {
                        $_staff_info['old_achievement'] = StoreStaffCommission::where("staff_id", $_staff['id'])->whereBetween("created_at", [$start, $end])->where("scene", 1)->sum("achievement");
                        $ladder_info = StoreStaffCommissionLadder::where("type", 1)->where("store_id", $this->store_id)->where("start", "<", $_staff_info['old_achievement'])->where("radio", ">", 0)->orderBy("start", "desc")->first();
                    } else {
                        $_staff_info['old_achievement'] = StoreStaffCommission::where("staff_id", $_staff['id'])->whereBetween("created_at", [$start, $end])->where("scene", 7)->sum("achievement");
                        $ladder_info = StoreStaffCommissionLadder::where("type", 5)->where("store_id", $this->store_id)->where("start", "<", $_staff_info['old_achievement'])->where("radio", ">", 0)->orderBy("start", "desc")->first();
                    }
                }

                $zd_set = StoreStaffProductSet::where("job_id", $_staff_info["job_id"])->where('type_data', 6)->first();
                if (!empty($zd_set)) {
                    $_staff_info['radio'] = $zd_set->radio;
                    if (empty($use_card_type) && $commissionSet->fkk_switch)
                        $_staff_info['radio'] = $zd_set->fkk_radio;
                    if ($zd_set->compute_mode == 1) {  //平均分配或独立分配
                        $_staff_info['money'] = sprintf('%.2f', $price * $_staff_info['radio'] / 100);
                        $_staff_info['achievement'] = sprintf('%.2f', $price);
                    } else {
                        $_staff_info['money'] = sprintf('%.2f', $price * $_staff_info['radio'] / 100 / count($input_money['staffs']));
                        $_staff_info['achievement'] = sprintf('%.2f', $price / count($input_money['staffs']));
                    }
                    if (!empty($ladder_info)) {
                        $radio = $ladder_info->radio ?? 0;
                        if ($zd_set->compute_mode == 1) {
                            $_staff_info['ladder_money'] = sprintf('%.2f', $price * $radio / 100);
                        } else {
                            $_staff_info['ladder_money'] = sprintf('%.2f', $price * $radio / count($input_money['staffs']) / 100);
                        }
                        $_staff_info['money'] = sprintf('%.2f', $_staff_info['money'] + $_staff_info['ladder_money']);
                    }
                }
                $_staff['commission'] = $_staff_info;

                $money->staffs[] = $_staff;
            }

            //各项使用的券
            $coupon_discount_id = $request->post('coupon_discount_id') ?? 0;      //折扣券id
            $coupon_full_minus_id = $request->post('coupon_full_minus_id') ?? 0;  //满减券id
            $full_minus_id = $request->post('full_minus_id') ?? 0;    //商家满减
            $money_reward_switch = $request->post('money_reward_switch') ?? 1;    //是否使用返利金
            //可使用折扣卷
            $coupon_discount_canuse = [];
            //可使用满减卷
            $coupon_full_minus_canuse = [];
            //最大满减剪掉的金额
            $coupon_full_minus_money = 0;
            //满减券使用的id
            $coupon_full_minus_use_id = 0;
            //优惠金额
            $coupon_discount_money = 0;    //最大折扣剪掉的金额
            $coupon_discount_money_use_id = 0;
            $full_minus_price = 0;
            $full_minus_discount = 0;
            $full_minus = [];
            $full_minus_info = [];

            if ($coupon_discount_id && $coupon_full_minus_id) {
                return failed("用户折扣券和满减券不可同时使用");
            }
            /**
             * if (empty($coupon_discount_id) && empty($coupon_full_minus_id) && $secend == 1) {
             * // 折扣
             * if ($coupon_discount) {
             * array_multisort(array_column($coupon_discount, 'discount'), SORT_ASC, $coupon_discount);
             * if ($real_collect - $bargain_price_money >= 0) {
             * $coupon_discount_money = round(($real_collect - $bargain_price_money) * (100 - $coupon_discount[0]["discount"]) / 100, 2);
             * $coupon_discount_money_use_id = $coupon_discount[0]["id"];
             * $coupon_discount_canuse = $coupon_discount;
             * }
             * }
             *
             * //满减
             * if ($coupon_full_minus) {
             * array_multisort(array_column($coupon_full_minus, 'reduce'), SORT_DESC, $coupon_full_minus);
             * if ($real_collect - $bargain_price_money >= 0) {
             * foreach ($coupon_full_minus as $cg) {
             * if ($real_collect - $bargain_price_money >= $cg["full"]) {
             * if ($cg["reduce"] > $coupon_full_minus_money) {
             * $coupon_full_minus_money = $cg["reduce"];
             * $coupon_full_minus_use_id = $cg["id"];
             * }
             * $coupon_full_minus_canuse[] = $cg;
             * }
             * }
             * }
             * }
             *
             * if ($real_collect - $bargain_price_money > 0) {
             * if ($coupon_full_minus_money > $coupon_discount_money) {
             * $real_collect = $real_collect - $coupon_full_minus_money;
             * $coupon_discount_money_use_id = 0;
             * $coupon_discount_money == 0;
             * } else {
             * $real_collect = $real_collect - $coupon_discount_money;
             * $coupon_full_minus_use_id = 0;
             * $coupon_full_minus_money = 0;
             * }
             * } else {
             * $coupon_discount_money_use_id = 0;
             * $coupon_discount_money = 0;
             * $coupon_full_minus_use_id = 0;
             * $coupon_full_minus_money = 0;
             * }
             * } else {
             * //可用的折扣券
             * $coupon_discount_canuse = $coupon_discount;
             * //可用的满减券
             * if ($coupon_full_minus) {
             * array_multisort(array_column($coupon_full_minus, 'reduce'), SORT_DESC, $coupon_full_minus);
             * foreach ($coupon_full_minus as $cg) {
             * if ($real_collect >= $cg["full"]) {
             * $coupon_full_minus_canuse[] = $cg;
             * }
             * }
             * }
             * //折扣
             * if ($coupon_discount_id) {
             * if ($real_collect - $bargain_price_money > 0) {
             * $coupon_discount_info = UsersCoupon::leftJoin("store_coupon", "users_coupon.coupon_id", "=", "store_coupon.id")->where("users_coupon.id", $coupon_discount_id)->where("users_coupon.is_delete", 0)->where("users_coupon.status", UsersCoupon::not_used)->withoutGlobalScope('delete')->first();
             * throw_if(empty($coupon_discount_info), new \Exception('折扣券状态异常，请刷新后重试'));
             * $coupon_discount_money = round(($real_collect - $bargain_price_money) * (100 - $coupon_discount_info["discount"]) / 100, 2);
             * $coupon_discount_money_use_id = $coupon_discount_id;
             * $real_collect = $real_collect - $coupon_discount_money;
             * }
             * }
             * if ($coupon_full_minus_id) {
             * if ($real_collect - $bargain_price_money > 0) {
             * $coupon_full_minus_info = UsersCoupon::leftJoin("store_coupon", "users_coupon.coupon_id", "=", "store_coupon.id")->where("users_coupon.id", $coupon_full_minus_id)->where("users_coupon.status", UsersCoupon::not_used)->where("users_coupon.is_delete", 0)->withoutGlobalScope('delete')->first();
             * throw_if(empty($coupon_full_minus_info), new \Exception('满减券状态异常，请刷新后重试'));
             * $coupon_full_minus_use_id = $coupon_full_minus_id;
             * $real_collect = $real_collect - $coupon_full_minus_info["reduce"];
             * $coupon_full_minus_money = $coupon_full_minus_info["reduce"];
             * }
             * }
             * }
             *
             * //增加店铺满减
             * //店铺满减   状态为正常的所有列表数据
             * $full_minus = StoreFullMinus::where("full", "<", $real_collect)->where("store_id", $this->store_id)->where("start_time", "<", time())->where("end_time", ">", time())->where("status", 1)->get();
             * if ($real_collect > 0) {
             * if ($full_minus_id || $secend != 1) {
             * $full_minus_info = StoreFullMinus::where("id", $full_minus_id)->where("store_id", $this->store_id)->where("status", 1)->first();
             * //throw_if(empty($full_minus_info), new \Exception('店铺满减券状态异常，请刷新后重试'));
             * $full_minus_discount = $full_minus_info->minus ?? 0;
             * } else {
             * foreach ($full_minus as $f) {
             * if ($f->minus > $full_minus_discount) {
             * $full_minus_discount = $f->minus;
             * $full_minus_id = $f->id;
             * $full_minus_info = $f;
             * }
             * }
             * }
             *
             * if ($full_minus_discount > 0) {
             * $real_collect = $real_collect - $full_minus_discount;
             * }
             * }
             */
            //返利金
            $money_reward = $users->money_reward;
            $total_money_reward = $users->money_reward;
            if ($real_collect > 0) {
                if ($money_reward_switch > 0) {
                    //返利金
                    if ($money_reward > 0) {
                        if ($money_reward > $real_collect) {
                            $money_reward = $real_collect;
                            $real_collect = 0;
                        } else {
                            $real_collect = $real_collect - $money_reward;
                        }
                    }
                } else {
                    $money_reward = 0;
                }
            } else {
                $money_reward = 0;
            }
            $order->update([
                'total' => $total,
                'real_collect' => $real_collect
            ]);
            $data['total'] = round($total, 2);
            $data['products'] = $new_products;
            $data['real_collect'] = round($real_collect, 2);
            $data['coupon_discount_canuse'] = $coupon_discount_canuse;
            $data['coupon_full_minus_canuse'] = $coupon_full_minus_canuse;
            $data['coupon_discount_money_use_id'] = $coupon_discount_money_use_id;
            $data['coupon_full_minus_use_id'] = $coupon_full_minus_use_id;
            $data['full_minus_use_id'] = $full_minus_id;
            $data['full_minus'] = $full_minus;
            $data['full_minus_info'] = $full_minus_info;
            $data['total_money_reward'] = round($total_money_reward, 2);
            $data['money_reward'] = round($money_reward, 2);
            $data['custom'] = $order->custom;
            $data['money'] = $money;
            $data['user'] = $order->user;
        } catch (\Exception $exception) {
            if (app()->environment('local', 'test')) throw $exception;
            return failed($exception->getMessage());
        }
        return success($data);
    }

    /**
     * 支付
     *
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function payment(Request $request): array
    {
        $id = $request->post('id', 0); // 订单ID
        $remark = $request->post('remark', "");
        $products = json_decode($request->post('products', '[]'), true);
        $money = json_decode($request->post('money', '[]'), true);
        $coupon_discount_id = $request->post('coupon_discount_id', 0);
        $coupon_full_minus_id = $request->post('coupon_full_minus_id', 0);
        $full_minus_id = $request->post('full_minus_id', 0);
        $money_reward_switch = $request->post('money_reward_switch', 0);
        $discount = $request->post('discount', 0);
        $created_at = strtotime($request->post('created_at', now()->toDateTimeString()));
        $payment_method = $request->post('payment_method', '');
        $money_reward = 0;
        $record_ids = '';
        $zk_card_res = [];

        $autograph = $request->input('autograph');
        if (!empty($payment_method) && !is_array($payment_method)) {
            $payment_method = json_decode($payment_method, true);
        } else {
            $payment_method = [];
        }

        $order = StoreCarOrder::where("id", $id)->first();

        if (empty($order)) return failed('订单不存在或已被删除');

        if ($order->status == 2) return failed('不能重复支付');

        if (empty($products) && empty($money)) return failed("请选择商品或输入消费金额");

        foreach ($products as $_product) {
            StoreCarOrderProduct::where([
                'order_id' => $order->id,
                'id' => $_product['id']
            ])->update([
                "use_card_id" => $_product['use_card_id'] ?? 0,
                "use_card_type" => $_product['use_card_type'] ?? 0,
                "use_coupon_id" => $_product['use_coupon_id'] ?? 0,
                "use_coupon_type" => $_product['use_coupon_type'] ?? 0,
                "pay_price" => $_product['users_product']['product_price'],
                "user_product_id" => $_product['users_product']['product_id'],
            ]);

            foreach ($_product['staff_arr'] as $_staff) {
                StoreCarOrderProductStaff::updateOrCreate([
                    'order_id' => $order->id,
                    'order_product_id' => $_product['id'],
                    'staff_id' => $_staff['staff_id']
                ], [
                    'product_price' => $_staff['product_price'],
                    'money' => $_staff['money'],
                ]);
            }
        }
        StoreCarOrder::where([
            'id' => $order->id,
        ])->update([
            'money' => $money['price'],
            "money_use_card_id" => empty($money['use_card']) ? 0 : $money['use_card']['id'],
            "money_use_card_type" => empty($money['use_card']) ? 0 : $money['use_card']['card_type'],
        ]);
        foreach ($money['staffs'] as $_staff) {
            StoreCarOrderMoneyStaff::updateOrCreate([
                'order_id' => $order->id,
                'staff_id' => $_staff['id']
            ], [
                'price' => $_staff['money'],
                'money' => $_staff['achievement'],
            ]);
        }

        $users_id = $order->user_id;

        $sync_user_store_id = $this->getSyncStoreId(Store::SYNC_TYPE_USER);

        DB::beginTransaction();
        try {
            if (!empty($money) && $money['old_price'] > 0) {
                $use_card_id = $money['use_card_id'] ?? 0;
                $use_card_type = $money['use_card_type'] ?? 0;

                $_staffs = [];
                if (!empty($money['staffs'])) {
                    foreach ($money['staffs'] as $staff) {
                        $staff['staff_id'] = $staff['id'];
                        $_staffs[] = $staff;
                    }
                }
                $_discount = 0;
                if (!in_array($use_card_type, [2, 4])) {
                    if ($money['price'] < $discount) $_discount = $money['price'];
                    else $_discount = $discount;
                    $discount -= $_discount;
                }
                $_pay_method = [];

                if (!empty($payment_method)) {
                    $_real_money = $money['price'] - $_discount;
                    if ($payment_method[0]['money'] < $_real_money) $_real_money = $payment_method[0]['money'];

                    $_pay_method = [['id' => $payment_method[0]['id'], 'money' => $_real_money]];
                    $payment_method[0]['money'] = $payment_method[0]['money'] - $_pay_method['0']['money'];

                }

                $consume_data = [
                    'users_id' => $users_id,
                    'store_id' => $this->store_id,
                    'admin_id' => $this->admin_id,
                    'sync_user_store_id' => $this->getSyncStoreId(Store::SYNC_TYPE_USER),
                    'remark' => $remark,
                    'users_card_id' => $use_card_type == 4 ? $use_card_id : 0, // 折扣卡卡片ID
                    'consumption_amount' => $money['old_price'], // 储值卡消费金额
                    'money' => $money['old_price'], // 没用卡时用的消费金额
                    'products' => [],
                    'staff_arr_all' => $_staffs,
                    'car_number' => $order->car_number,
                    'created_at' => $created_at,
                    'coupon_full_minus_id' => $coupon_full_minus_id,
                    'coupon_discount_id' => $coupon_discount_id,
                    'coupon_bargain_price_ids' => 0,
                    'coupon_gift_ids' => 0,
                    'money_reward_switch' => $money_reward_switch,
                    'discount' => $_discount,
                    'full_minus_id' => $full_minus_id,
                    'payment_method' => $_pay_method,
                    'car_order_id' => $order->id
                ];

                if (in_array($use_card_type, [2, 4])) { // 2 储值卡 4 折扣卡
                    $record_id = UsersRecordDao::consumeMoney($consume_data);
                } else {
                    $record_id = UsersRecordDao::consumeAllNew($consume_data);
                }
                $record_ids = $record_ids . "," . $record_id;
                $record = $this->getRecord($record_id);
                $money_reward += $record->money_reward;

                $order->use_card_money += $record->use_money;
            }

            if (count($products) > 0) {
                $fast_p = [];
                $coupon_bargain_price_ids = [];
                $coupon_gift_ids = [];
                $staff_arr = [];

                $kk_consume_data = [];
                foreach ($products as $product) {
                    $use_card_id = $product['use_card_id'] ?? 0;
                    $use_card_type = $product['use_card_type'] ?? 0;
                    $use_coupon_id = $product['use_coupon_id'] ?? 0;
                    $use_coupon_type = $product['use_coupon_type'] ?? 0;

                    $new_data = [
                        'id' => in_array($use_card_type, [1, 3]) ? $product['users_product']['product_id'] : $product['product_id'],
                        'number' => 1,
                        'price' => $product['users_product']['product_price'],
                        'staff' => []
                    ];

                    if (!empty($product['staff_arr'])) {
                        $new_data['staff'] = array_column($product['staff_arr'], 'staff_id');
                        foreach ($product['staff_arr'] as &$staff) {
                            $staff['achievement'] = $staff['product_price'];
                        }
                    }

                    if (!empty($use_card_type)) {
                        if (!isset($kk_consume_data[$use_card_id])) {
                            $kk_consume_data[$use_card_id] = [
                                'users_id' => $users_id,
                                'card_type' => $use_card_type,
                                'store_id' => $this->store_id,
                                'admin_id' => $this->admin_id,
                                'sync_user_store_id' => $sync_user_store_id,
                                'users_card_id' => $use_card_id,
                                'users_product' => in_array($use_card_type, [1, 3]) ? [$new_data] : [],
                                'products' => in_array($use_card_type, [2, 4]) ? [$new_data] : [],
                                'staff_arr' => [$product['staff_arr']] ?? [],
                                'car_number' => $order->car_number,
                                'remark' => $remark,
                                'created_at' => $created_at,
                                'car_order_id' => $order->id
                            ];
                        } else {
                            if (in_array($use_card_type, [1, 3])) {
                                $kk_consume_data[$use_card_id]['users_product'][] = $new_data;
                            } else {
                                $kk_consume_data[$use_card_id]['products'][] = $new_data;
                            }
                            $kk_consume_data[$use_card_id]['staff_arr'][] = $product['staff_arr'] ?? [];
                        }
                    } else {
                        $fast_p[] = $new_data;
                        if (!empty($product['staff_arr'])) {
                            $staff_arr[] = $product['staff_arr'];
                        }
                        if (!empty($use_coupon_id)) {
                            if ($use_coupon_type == 2) {
                                $coupon_gift_ids[] = $use_coupon_id;
                            } else {
                                $coupon_bargain_price_ids[] = $use_coupon_id;
                            }
                        }
                    }
                }
                /**
                 * 非扣卡消费
                 */
                if (!empty($fast_p)) {
                    if (count($coupon_bargain_price_ids) > 0) {
                        $coupon_bargain_price_ids_str = implode(',', $coupon_bargain_price_ids);
                    } else {
                        $coupon_bargain_price_ids_str = "";
                    }
                    if (count($coupon_gift_ids) > 0) {
                        $coupon_gift_ids_str = implode(',', $coupon_gift_ids);
                    } else {
                        $coupon_gift_ids_str = "";
                    }
                    $consume_data = [
                        'users_id' => $users_id,
                        'store_id' => $this->store_id,
                        'admin_id' => $this->admin_id,
                        'sync_user_store_id' => $sync_user_store_id,
                        'remark' => $remark,
                        'users_card_id' => 0,
                        'products' => $fast_p,
                        'staff_arr' => Arr::collapse($staff_arr),
                        'car_number' => $order->car_number,
                        'created_at' => $created_at,
                        'coupon_full_minus_id' => $coupon_full_minus_id,
                        'coupon_discount_id' => $coupon_discount_id,
                        'coupon_bargain_price_ids' => $coupon_bargain_price_ids_str,
                        'coupon_gift_ids' => $coupon_gift_ids_str,
                        'money_reward_switch' => $money_reward_switch,
                        'discount' => $discount,
                        'full_minus_id' => $full_minus_id,
                        'payment_method' => $payment_method,
                        'car_order_id' => $order->id
                    ];
                    $record_id = UsersRecordDao::consumeAllNew($consume_data);
                    $record_ids = $record_ids . "," . $record_id;
                    if ($money_reward_switch) {
                        $money_reward += $this->getRecord($record_id, 'money_reward');
                    }
                }

                /**
                 * 扣卡
                 */
                foreach ($kk_consume_data as $_kk_consume_data) {
                    $_kk_consume_data['staff_arr'] = Arr::collapse($_kk_consume_data['staff_arr']);

                    $record_id = 0;

                    switch ($_kk_consume_data['card_type']) {
                        case 1: // 次卡
                            $record_id = UsersRecordDao::consume($_kk_consume_data);
                            break;
                        case 2: // 储值卡
                            $_kk_consume_data['users_card_id'] = 0;
                            $record_id = UsersRecordDao::consumeMoney($_kk_consume_data);
                            break;
                        case 3: // 时长卡
                            $record_id = UsersRecordDao::consume($_kk_consume_data);
                            break;
                        case 4: // 折扣卡
                            $record_id = UsersRecordDao::consumeMoney($_kk_consume_data);
                            break;
                    }

                    $record = $this->getRecord($record_id);

                    if ($_kk_consume_data['card_type'] == 2) {
                        $order->use_card_money += $record->use_money;
                    } elseif ($_kk_consume_data['card_type'] == 4) {
                        $card = UsersCard::with('card')->find($_kk_consume_data['users_card_id']);
                        if (isset($zk_card_res[$record->users_card_id])) {
                            $zk_card_res[$record->users_card_id]['use_money'] += $record->use_money;
                            $zk_card_res[$record->users_card_id]['balance'] += $card->use_money;
                        } else {
                            $zk_card_res[$record->users_card_id] = ['name' => $card->card->name, 'balance' => $card->use_money, 'use_money' => $record->use_money];
                        }
                    }
                    if ($money_reward_switch) {
                        $money_reward += $record->money_reward;
                    }

                    if (in_array($_kk_consume_data['card_type'], [1, 3])) {
                        $logs = UsersLog::where('data_id', $record_id)->get();
                        foreach ($logs as $log) {
                            StoreCarOrderProduct::where([
                                'order_id' => $order->id,
                                'user_product_id' => $log->users_product_id
                            ])->update([
                                "card_surplus_number" => $log->after
                            ]);
                        }
                    }

                    $record_ids = $record_ids . "," . $record_id;
                }
            }

            $zk = [];
            foreach ($zk_card_res as $zk_card_re) {
                $zk[] = $zk_card_re;
            }
            $order->zk_card_res = json_encode($zk);
            $order->payment_record_id = $record_ids;
            $order->coupon_full_minus_id = $coupon_full_minus_id;
            $order->full_minus_id = $full_minus_id;
            $order->coupon_discount_id = $coupon_discount_id;
            $order->autograph = $autograph;
            $order->real_collect = $order->real_collect - $discount;
            $order->discount = $discount;
            $order->money_reward_switch = $money_reward_switch;
            $order->money_reward = $money_reward;
            $order->status = StoreCarOrder::STATUS_FINISH;
            $order->consume_remark = $remark;
            $order->operator_id = $this->admin_id;
            $order->created_at = $created_at;
            if (empty($order->finished_at)) $order->finished_at = now()->toDateTimeString();
            $order->save();
            DB::commit();
            return success();
        } catch (\Exception $exception) {
            DB::rollback();
            if (app()->environment('local', 'test')) throw $exception;
            return failed($exception->getMessage());
        }
    }

    public function getRecord($id, $key = null)
    {
        $record = UsersRecord::find($id);
        if ($record) {
            if ($key) return $record->$key;
            return $record;
        }
        return 0;
    }

    /**
     * 删除
     *
     * @param Request $request
     * @return array
     */
    public function delete(Request $request)
    {
        $id = $request->input('id', 0);

        $order = StoreCarOrder::find($id);
        if (empty($order)) return failed('订单未找到');

        $order->is_delete = 1;
        $order->save();

        return success();
    }
}
