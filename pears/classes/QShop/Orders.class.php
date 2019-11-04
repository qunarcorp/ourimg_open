<?php

/**
 * 积分商城订单
 * Class QShop_Orders
 */

class QShop_Orders
{
    /**
     * @param int $perPage
     * @param null $shippedStatus
     * @param null $searchQuery
     * @param null $username
     * @return array
     */
    public function paginate(int $perPage, $shippedStatus = null, $searchQuery = null, $username = null)
    {
        $countSql = "select count(*) as aggregate from public.exchange_order " . $this->where($shippedStatus, $searchQuery, $username);
        $total = (int) DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $currentPage = $this->page();
        $lastPage = ceil($total / $perPage);
        $offset = ($currentPage - 1) * $perPage;

        $sql = "select eo.order_id, eo.product_id, pi.title as product_title, pi.img_url->>0 as product_master_img, eo.product_points, 
                  eo.exchange_count, eo.exchange_points, eo.mobile, eo.address, case when eo.state = 'exchange_success' then '兑换成功' 
                  when eo.state = 'exchange_fail' then '兑换失败' when eo.state = 'shipped' then '已发货' else '' end as order_status, 
                  eo.username, eo.create_time 
                from public.exchange_order eo
                inner join public.product_info pi on pi.id = eo.product_id "
            . $this->where($shippedStatus, $searchQuery, $username)
            . $this->orderBy()
            . " offset {$offset} limit {$perPage}";

        $data = (array) DBSLAVE::GetQueryResult($sql, false);

        return compact("total", "currentPage", "lastPage", "perPage", "data");
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
     * @return string
     */
    private function orderBy()
    {
        return "order by eo.id desc";
    }

    /**
     * @param null $deliverStatus
     * @param null $searchQuery
     * @param null $username
     * @return string
     */
    private function where($deliverStatus = null, $searchQuery = null, $username = null)
    {
        $where = [];
        if (in_array($deliverStatus, ["exchange_success", "exchange_fail", "shipped"])) {
            $where[] = " eo.state = '{$deliverStatus}' ";
        }

        if (! is_null($searchQuery)) {
            $searchQuery = DB::EscapeString($searchQuery);
            $where[] = " (eo.order_id ~ '{$searchQuery}' or eo.username ~ '{$searchQuery}') ";
        }

        if (! is_null($username)) {
            $username = DB::EscapeString($username);
            $where[] = " eo.username = '{$username}' ";
        }

        return empty($where) ? "" : " where " . implode(" and ", $where);
    }

    /**
     * @param $orderId
     * @return bool
     */
    public static function exist($orderId)
    {
        $countSql = "select count(*) as aggregate from public.exchange_order where order_id = '{$orderId}' ";
        return !! DBSLAVE::GetQueryResult($countSql)["aggregate"];
    }

    /**
     * @param $orderId
     * @param $username
     * @return bool
     */
    public function ship($orderId, $username)
    {
        $sql = <<<SQL
    update public.exchange_order set state = 'shipped', ship_user = '{$username}', ship_time = now(), update_time = now()
    where order_id = '{$orderId}' and state != 'shipped'
SQL;
        return !! pg_affected_rows(DB::Query($sql));
    }
}