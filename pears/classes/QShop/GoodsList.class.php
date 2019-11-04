<?php

/**
 * 积分商城商品列表
 * Class QShop_GoodsList
 */

class QShop_GoodsList
{
    /**
     * @param int $perPage
     * @param $orderBy
     * @param $filterCondition
     * @param $username
     * @return array
     */
    public function paginate(int $perPage, $orderBy, $filterCondition, $username)
    {
        $countSql = "select count(*) as aggregate from public.product_info " . $this->where($filterCondition, $username);
        $total = (int) DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $currentPage = $this->page();
        $lastPage = ceil($total / $perPage);
        $offset = ($currentPage - 1) * $perPage;

        $sql = "select id, eid, title, description, img_url->>0 as master_img, browse, on_sale, exchange_begin_time, exchange_end_time, 
         price, points, stock, remain_stock, sale_time from public.product_info "
            . $this->where($filterCondition, $username)
            . $this->orderBy($orderBy)
            . " offset {$offset} limit {$perPage}";
        $list = (array) DBSLAVE::GetQueryResult($sql, false);
        $imgSize = self::getImgSize($list);
        $data = array_map(function($goods) use ($imgSize) {
            return QShop_Goods::explainGoods($goods, $imgSize);
        }, $list);

        return compact("total", "currentPage", "lastPage", "perPage", "data");
    }

    /**
     * get img size
     * @param $list
     * @return array
     */
    public static function getImgSize($list)
    {
        $imgKeyStr = implode(", ", array_map(function($imgKey){
            return "'{$imgKey}'";
        }, array_unique(array_merge(... array_map(function ($row){
            return array_merge(
                isset($row["master_img"]) ? [$row["master_img"]] : [],
                isset($row["img_url"]) ? json_decode($row["img_url"], true) : []
            );
        }, $list)))));

        $sql = "select img_key, width, height from public.goods_img_record where img_key in ({$imgKeyStr}) ";

        return array_replace_key(DBSLAVE::GetQueryResult($sql, false), 'img_key');
    }

    /**
     * 获取当前页码
     * @return mixed
     */
    private function page()
    {
        return max(1, intval($_GET["page"]));
    }

    /**
     * 构造order by
     * @param $orderBy
     * @return string
     */
    private function orderBy($orderBy)
    {
        switch ($orderBy) {
            case "hot":
                $orderByQuery = "exchange_count desc, browse desc, id desc";
                break;
            case "sale_time_asc":
                $orderByQuery = "sale_time asc, id desc";
                break;
            case "sale_time_desc":
                $orderByQuery = "sale_time desc, id desc";
                break;
            case "points_asc":
                $orderByQuery = " points asc, id desc";
                break;
            case "points_desc":
                $orderByQuery = "points desc, id desc";
                break;
            default:
                $orderByQuery = "exchange_count desc, browse desc, points asc, sale_time asc, id desc";
                break;
        }

        return " order by " . $orderByQuery;
    }

    /**
     * 构造where条件
     * @param $filterCondition
     * @param null $username
     * @return string
     */
    private function where($filterCondition, $username = null)
    {
        if (strpos($filterCondition, ',') !== false) {
            $filterCondition = explode(',', $filterCondition);
            sort($filterCondition);
            $filterCondition = implode(",", $filterCondition);
        }
        switch ($filterCondition) {
            case "only_in_stock":
                $whereQuery = " remain_stock > 0 ";
                break;
            case "can_exchange":
                $whereQuery = is_null($username) ? "" : " points <= (select current_points from public.system_user where username = '{$username}' limit 1) ";
                break;
            case "can_exchange,only_in_stock":
                $whereQuery = implode(" and ", array_merge(
                    [
                        " remain_stock > 0 "
                    ],
                    is_null($username) ? [] : [" points <= (select current_points from public.system_user where username = '{$username}' limit 1) "]
                ));
                break;
            default:
                $whereQuery = "";
                break;
        }

        return empty($whereQuery) ? " where on_sale = 't' " : " where on_sale = 't' and " . $whereQuery;
    }

    /**
     * paginate for manage
     * @param int $perPage
     * @param $searchQuery
     * @param $goodsSaleStatus
     * @param $orderBy
     * @return array
     */
    public function paginateForManage(int $perPage, $searchQuery, $goodsSaleStatus, $orderBy)
    {
        $countSql = "select count(*) as aggregate from public.product_info " . $this->whereForManage($searchQuery, $goodsSaleStatus);
        $total = (int) DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $currentPage = $this->page();
        $lastPage = ceil($total / $perPage);
        $offset = ($currentPage - 1) * $perPage;

        $sql = "select id, eid, title, description, img_url->>0 as master_img, browse, on_sale, exchange_begin_time, exchange_end_time, 
         price, points, stock, remain_stock, last_update_username, sale_time, create_time, update_time from public.product_info "
            . $this->whereForManage($searchQuery, $goodsSaleStatus)
            . $this->orderByForManage($orderBy)
            . " offset {$offset} limit {$perPage}";
        $list = (array) DBSLAVE::GetQueryResult($sql, false);
        $imgSize = self::getImgSize($list);
        $data = array_map(function($goods) use ($imgSize) {
            $goods = QShop_Goods::explainGoods($goods, $imgSize);
            return $goods;
        }, $list);

        return compact("total", "currentPage", "lastPage", "perPage", "data");
    }

    /**
     * 构造order by for manage
     * @param $orderBy
     * @return string
     */
    private function orderByForManage($orderBy)
    {
        switch ($orderBy) {
            case "stock_asc":
                $orderByQuery = "remain_stock desc, id desc";
                break;
            case "stock_desc":
                $orderByQuery = "remain_stock asc, id desc";
                break;
            case "sale_time_asc":
                $orderByQuery = "sale_time asc, id desc";
                break;
            case "sale_time_desc":
                $orderByQuery = "sale_time desc, id desc";
                break;
            case "points_asc":
                $orderByQuery = " points asc, id desc";
                break;
            case "points_desc":
                $orderByQuery = "points desc, id desc";
                break;
            default:
                $orderByQuery = "id desc";
                break;
        }

        return " order by " . $orderByQuery;
    }

    /**
     * 构造where条件 for manage
     * @param $searchQuery
     * @param $goodsSaleStatus
     * @return string
     */
    private function whereForManage($searchQuery, $goodsSaleStatus)
    {
        $condition = [];

        if ($searchQuery) {
            $searchQuery = DB::EscapeString($searchQuery);
            array_push($condition, " ( eid::text ~ '{$searchQuery}' or  title ~ '{$searchQuery}'  ) ");
        }

        switch ($goodsSaleStatus){
            case "no_sale":
                array_push($condition, " on_sale is null ");
                break;
            case "off_sale":
                array_push($condition, " on_sale = 'f' ");
                break;
            case "no_stock":
                array_push($condition, " on_sale = 't' and remain_stock = 0 ");
                break;
            case "can_exchange":
                array_push($condition, " on_sale = 't' and remain_stock > 0 and ((exchange_begin_time is null and exchange_end_time is null) or (exchange_begin_time <= now() and exchange_end_time > now())) ");
                break;
            default:
                break;
        }
        if (in_array($goodsSaleStatus, ["on_sale", "off_sale"])) {
            $goodsSaleStatus = $goodsSaleStatus == 'on_sale' ? 't' : 'f';
            array_push($condition, " on_sale = '{$goodsSaleStatus}' ");
        }

        return empty($condition) ? "" : " where " . implode(" and ", $condition);
    }
}