<?php

/**
 * 积分商城商品类
 * Class QShop_Goods
 */

class QShop_Goods
{
    /**
     * 创建商品
     * @param array $goodsInfo
     * @param $operateUser
     * @return bool
     */
    public function create(array $goodsInfo, $operateUser)
    {
        DB::beginTransaction();
        $storeData = [
            "title" => DB::EscapeString($goodsInfo["title"]),
            "description" => DB::EscapeString($goodsInfo["description"]),
            "img_url" => DB::EscapeString(json_encode($goodsInfo["img_url"], JSON_UNESCAPED_UNICODE)),
            "exchange_begin_time" => is_null($goodsInfo["exchange_begin_time"]) ? $goodsInfo["exchange_begin_time"] : DB::EscapeString(date("Y-m-d H:i:s", strtotime($goodsInfo["exchange_begin_time"]))),
            "exchange_end_time" => is_null($goodsInfo["exchange_end_time"]) ? $goodsInfo["exchange_end_time"] : DB::EscapeString(date("Y-m-d H:i:s", strtotime($goodsInfo["exchange_end_time"]))),
            "exchange_description" => DB::EscapeString($goodsInfo["exchange_description"]),
            "price" => number_format($goodsInfo["price"], 2, '.', ''),
            "points" => intval($goodsInfo["points"]),
            "stock" => intval($goodsInfo["stock"]),
            "remain_stock" => intval($goodsInfo["stock"]), //剩余库存
            "detail_title" => DB::EscapeString($goodsInfo["detail_title"]),
            "detail" => DB::EscapeString($goodsInfo["detail"]),
            "detail_img" => DB::EscapeString(json_encode($goodsInfo["detail_img"], JSON_UNESCAPED_UNICODE)),
            "create_username" => DB::EscapeString($operateUser),
            "last_update_username" => DB::EscapeString($operateUser),
        ];
        if ($goodsInfo["publish_status"]) {
            $storeData["on_sale"] = "t";
            $storeData["sale_time"] = date("Y-m-d H:i:s");
        }
        $goodsId = DB::Insert("public.product_info", $storeData, "id");
        // 更新商品eid
        if (! $goodsId || ! DB::Update("public.product_info", $goodsId, ["eid" => (new IDEncipher())->encrypt($goodsId)])) {
            DB::rollbackTransaction();
            return false;
        }

        DB::commitTransaction();
        return true;
    }

    /**
     * 更新商品信息
     * @param $goodsEid
     * @param array $goodsInfo
     * @param $operateUser
     * @return bool|resource
     */
    public function update($goodsEid, array $goodsInfo, $operateUser)
    {
        $updateData = [
            "title" => DB::EscapeString($goodsInfo["title"]),
            "description" => DB::EscapeString($goodsInfo["description"]),
            "img_url" => DB::EscapeString(json_encode($goodsInfo["img_url"], JSON_UNESCAPED_UNICODE)),
            "exchange_begin_time" => DB::EscapeString(date("Y-m-d H:i:s", strtotime($goodsInfo["exchange_begin_time"]))),
            "exchange_end_time" => DB::EscapeString(date("Y-m-d H:i:s", strtotime($goodsInfo["exchange_end_time"]))),
            "exchange_description" => DB::EscapeString($goodsInfo["exchange_description"]),
            "price" => number_format($goodsInfo["price"], 2, '.', ''),
            "points" => intval($goodsInfo["points"]),
            "stock" => intval($goodsInfo["stock"]),
            "detail" => DB::EscapeString($goodsInfo["detail"]),
            "detail_title" => DB::EscapeString($goodsInfo["detail_title"]),
            "detail_img" => DB::EscapeString(json_encode($goodsInfo["detail_img"], JSON_UNESCAPED_UNICODE)),
            "last_update_username" => DB::EscapeString($operateUser),
        ];

        $sql = <<<SQL
 update public.product_info 
 set title = '{$updateData["title"]}',  description = '{$updateData["description"]}',  img_url = '{$updateData["img_url"]}', 
 exchange_begin_time = '{$updateData["exchange_begin_time"]}',  exchange_end_time = '{$updateData["exchange_end_time"]}',
 exchange_description = '{$updateData["exchange_description"]}',  price = '{$updateData["price"]}',  points = '{$updateData["points"]}', 
 stock = '{$updateData["stock"]}',  remain_stock = (SELECT max(remaining_inventory) from (VALUES({$updateData["stock"]} - exchange_count),(0)) v(remaining_inventory)),
 detail = '{$updateData["detail"]}', detail_title = '{$updateData["detail_title"]}', detail_img = '{$updateData["detail_img"]}', last_update_username = '{$updateData["last_update_username"]}', sale_time = null 
 where eid = '{$goodsEid}'
SQL;
        return DB::Query($sql);
    }

    /**
     * 根据eid获取商品信息
     * @param $goodsEid
     * @return array
     */
    public function find($goodsEid)
    {
        $sql = <<<SQL
 select id, eid, title, description, img_url->>0 as master_img, img_url, browse, on_sale, exchange_begin_time, exchange_end_time, 
        exchange_description, price, points, stock, remain_stock, detail, detail_title, detail_img 
 from public.product_info
 where eid = '{$goodsEid}'
 limit 1
SQL;

        $goods = DBSLAVE::GetQueryResult($sql);

        return $goods ? self::explainGoods($goods, QShop_GoodsList::getImgSize([$goods])) : null;
    }

    /**
     * goods browse
     * @param $goodsEid
     */
    public function browse($goodsEid)
    {
        DB::Query(" update public.product_info set browse = browse + 1 where eid = '{$goodsEid}' ");
    }

    /**
     * 根据id获取商品信息
     * @param $goodsId
     * @return array
     */
    public function findById($goodsId)
    {
        $sql = <<<SQL
 select id, eid, title, description, img_url, browse, on_sale, exchange_begin_time, exchange_end_time, 
        exchange_description, price, points, stock, remain_stock, detail 
 from public.product_info
 where id = '{$goodsId}'
 limit 1
SQL;

        $goods = DBSLAVE::GetQueryResult($sql);

        return $goods ? self::explainGoods($goods) : null;
    }

    /**
     * 解析goods字段
     * @param $goods
     * @param $imgSize
     * @return mixed
     */
    public static function explainGoods($goods, $imgSize = [])
    {
        global $system_domain;

        array_key_exists("img_url", $goods)
            and $goods["img_url"] = array_map(function($img) use ($system_domain, $imgSize) {
                return isset($imgSize[$img])
                    ? [
                        "small" => QImg::getImgUrlResize([
                            "img" => $img,
                            "width" => $imgSize[$img]["width"],
                            "height" => $imgSize[$img]["height"],
                            "r_width" => 80,
                            "r_height" => 0,
                            'system_domain' => $system_domain,
                            "in" => "inner_domain"
                        ]),
                        "middle" => QImg::getImgUrlResize([
                            "img" => $img,
                            "width" => $imgSize[$img]["width"],
                            "height" => $imgSize[$img]["height"],
                            "r_width" => 350,
                            "r_height" => 0,
                            'system_domain' => $system_domain,
                            "in" => "inner_domain"
                        ]),
                        "big" => QImg::getImgUrlResize([
                            "img" => $img,
                            "width" => $imgSize[$img]["width"],
                            "height" => $imgSize[$img]["height"],
                            "r_width" => 800,
                            "r_height" => 0,
                            'system_domain' => $system_domain,
                            "in" => "inner_domain"
                        ]),
                        "original" => QImg::getImgUrl($img, $system_domain, "inner_domain")
                    ] : [
                        "small" => QImg::getImgUrl($img, $system_domain, "inner_domain"),
                        "middle" => QImg::getImgUrl($img, $system_domain, "inner_domain"),
                        "big" => QImg::getImgUrl($img, $system_domain, "inner_domain"),
                        "original" => QImg::getImgUrl($img, $system_domain, "inner_domain")
                    ];
        }, json_decode($goods["img_url"], true));

        array_key_exists("detail_img", $goods)
            and $goods["detail_img"] = array_map(function($img) use ($system_domain) {
                return QImg::getImgUrl($img, $system_domain, "inner_domain");
            }, json_decode($goods["detail_img"], true));

        array_key_exists("on_sale", $goods)
            and $goods["on_sale"] = empty($goods["on_sale"]) ? '' : $goods["on_sale"] == 't';

        array_key_exists("master_img", $goods)
            and $goods["master_img"] = isset($imgSize[$goods["master_img"]])
                    ? QImg::getImgUrlResize([
                            "img" => $goods["master_img"],
                            "width" => $imgSize[$goods["master_img"]]["width"],
                            "height" => $imgSize[$goods["master_img"]]["height"],
                            "r_width" => 275,
                            "r_height" => 0,
                            'system_domain' => $system_domain,
                            "in" => "inner_domain"
                        ])
                    : QImg::getImgUrl($goods["master_img"], $system_domain, "inner_domain");

        if (array_key_exists("exchange_begin_time", $goods)){
            $goods['exchange_begin_time'] = strtotime($goods['exchange_begin_time']);
            $goods['exchange_begin_time'] = ! $goods['exchange_begin_time'] ? ''
                : date("Y-m-d H:i:s", $goods['exchange_begin_time']);
        }

        if (array_key_exists("exchange_end_time", $goods)){
            $goods['exchange_end_time'] = strtotime($goods['exchange_end_time']);
            $goods['exchange_end_time'] = ! $goods['exchange_end_time'] ? ''
                : date("Y-m-d H:i:s", $goods['exchange_end_time']);
        }

        if (array_key_exists("create_time", $goods)){
            $goods['create_time'] = strtotime($goods['create_time']);
            $goods['create_time'] = ! $goods['create_time'] ? ''
                : date("Y-m-d H:i:s", $goods['create_time']);
        }

        if (array_key_exists("update_time", $goods)){
            $goods['update_time'] = strtotime($goods['update_time']);
            $goods['update_time'] = ! $goods['update_time'] ? ''
                : date("Y-m-d H:i:s", $goods['update_time']);
        }

        if (array_key_exists("sale_time", $goods)){
            $goods['sale_time'] = strtotime($goods['sale_time']);
            $goods['sale_time'] = ! $goods['sale_time'] ? ''
                : date("Y-m-d H:i:s", $goods['sale_time']);
        }

        $goods["goods_status"] = $goods["on_sale"] === false
            ? "已下架"
            : ($goods["on_sale"] == ''
                ? "未上架"
                : ($goods["remain_stock"] == 0
                    ? "已兑完"
                    : (empty($goods["exchange_begin_time"]) || (strtotime($goods["exchange_begin_time"]) <= time() && strtotime($goods["exchange_end_time"]) > time())
                        ? "可兑换"
                        : "非兑换时间")));
        return $goods;
    }

    /**
     * 上架
     * @param $goodsEid
     * @param $loginUserName
     * @return bool
     */
    public function onSale($goodsEid, $loginUserName)
    {
        return $this->toggleSaleStatus($goodsEid, $loginUserName, true);
    }

    /**
     * 下架
     * @param $goodsEid
     * @param $loginUserName
     * @return bool
     */
    public function offSale($goodsEid, $loginUserName)
    {
        return $this->toggleSaleStatus($goodsEid, $loginUserName, false);
    }

    /**
     * 商品上下架
     * @param $goodsEid
     * @param $loginUserName
     * @param bool|null $status
     * @return bool
     */
    public function toggleSaleStatus($goodsEid, $loginUserName, bool $status = null) : bool
    {
        $goods = ! $this->find($goodsEid)["on_sale"];
        if (is_null($status)) {
            $status = $goods["on_sale"];
        }

        $updateData = [
            "on_sale" => $status ? 't' : 'f',
            "last_update_username" => $loginUserName,
            "update_time" => date("Y-m-d H:i:s"),
        ];

        if (! strtotime($goods["sale_time"]) && $status) {
            $updateData["sale_time"] = date("Y-m-d H:i:s");
        }
        return DB::Update("public.product_info", $goodsEid, $updateData, "eid");
    }
}