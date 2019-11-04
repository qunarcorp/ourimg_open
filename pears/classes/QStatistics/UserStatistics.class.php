<?php

/**
 * 用户统计
 * Class QStatistics_UserStatistics
 */

class QStatistics_UserStatistics
{
    public function userList($pageSize = 10, $page = 1, $tabType, $sortField, $sortOrder, $query)
    {
        $baseSql = $this->getBaseSql();

        $where = [
            "(su.username like '%.%' or su.username = 'piao_qsight_provider')"
        ];
        if ($query) {
            $where[] = " ( su.username ~ '{$query}' or su.name ~ '{$query}' ) ";
        }

        if ($tabType == 'upload_user_num') {
            $where[] = " uis.img_num > 0 ";
        }

        if (! empty($where)) {
            $whereSql = " where " . implode(" and ", $where);
        }else{
            $whereSql = "";
        }

        $totalSql = <<<SQL
    {$baseSql}
    select count(1) as aggregate
        from public.system_user su
        left join user_img_statistics uis on uis.username = su.username
        {$whereSql}
SQL;
        $total = (int) DBSLAVE::GetQueryResult($totalSql)["aggregate"];
        $page = (int) $page;
        $pageSize = (int) $pageSize;
        $lastPage = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;

        $sort = $sortOrder == "descend" ? 'desc' : 'asc';
        if ($sortField == "first_visit_time"){
            $order = " first_visit_time {$sort} ";
        }elseif ($sortField == "img_num"){
            $order = " case when uis.img_num is null then 0 else uis.img_num end {$sort} ";
        }elseif ($sortField == "earliest_upload_time"){
            $order = " case when uis.earliest_upload_time is null then '1970-01-01' else uis.earliest_upload_time end {$sort} ";
        }elseif ($sortField == "auth_date"){
            $order = " case when su.auth_time is null then '1970-01-01' else su.auth_time end {$sort} ";
        }else{
            $order = " first_visit_time desc ";
        }

        $listSql = <<<SQL
    {$baseSql}
    select su.username, su.name, su.dept, su.create_time as first_visit_time, su.auth_time, img_num, uis.earliest_upload_time 
        from public.system_user su 
        left join user_img_statistics uis on uis.username = su.username
        {$whereSql}
        order by {$order} 
        limit {$pageSize} offset {$offset}
SQL;

        $list = DBSLAVE::GetQueryResult($listSql, false);
        $list = array_map(function($item){
            $item["dept"] = (is_null($item["dept"]) || $item["dept"] == 'null') ? '' : implode("/", array_filter(json_decode($item["dept"], true), function($item){
                return $item;
            }));
            $item["img_num"] = is_null($item["img_num"]) ? "0" : $item["img_num"];
            $item["auth_date"] = $item['auth_time'] ? date("Y-m-d", strtotime($item['auth_time'])) : '-';
            $item['earliest_upload_time'] = is_null($item['earliest_upload_time']) ? '-' : date('Y-m-d H:i:s', strtotime($item['earliest_upload_time']));
            $item['first_visit_time'] = is_null($item['first_visit_time']) ? '-' : date('Y-m-d H:i:s', strtotime($item['first_visit_time']));
            return array_except($item, 'auth_time');
        }, $list);

        return compact("total", "page", "pageSize", "lastPage", "list");
    }

    public function userStatistics()
    {
        $baseSql = $this->getBaseSql();

        $statisticSql = <<<SQL
        {$baseSql}
        select (
            select count(1) from user_img_statistics uis where uis.username like '%.%'
        ) as upload_user_num, (
            select count(1) from public.system_user su where su.username like '%.%'
        ) as visit_user_num
SQL;

        $statistic = DBSLAVE::GetQueryResult($statisticSql, true);

        return $statistic;
    }

    private function getBaseSql()
    {
        $baseSql = <<<SQL
    with user_img_statistics as (
        select username, count(1) as img_num, min(create_time) as earliest_upload_time 
            from public.img
            group by username
    )
SQL;
        return $baseSql;
    }

    public function allData()
    {
        $baseSql = $this->getBaseSql();

        $listSql = <<<SQL
    {$baseSql}
    select su.username, su.name, su.dept, su.create_time as first_visit_time, su.auth_time, img_num, uis.earliest_upload_time 
        from public.system_user su 
        left join user_img_statistics uis on uis.username = su.username
        where su.username like '%.%'
        order by first_visit_time desc 
SQL;

        $list = DBSLAVE::GetQueryResult($listSql, false);
        $list = array_map(function($item){
            $item["dept"] = (is_null($item["dept"]) || $item["dept"] == 'null') ? '' : implode("/", array_filter(json_decode($item["dept"], true), function($item){
                return $item;
            }));
            $item["img_num"] = is_null($item["img_num"]) ? "0" : $item["img_num"];
            $item["auth_date"] = $item['auth_time'] ? date("Y-m-d", strtotime($item['auth_time'])) : '-';
            $item['earliest_upload_time'] = is_null($item['earliest_upload_time']) ? '-' : date('Y-m-s H:i:s', strtotime($item['earliest_upload_time']));
            $item['first_visit_time'] = is_null($item['first_visit_time']) ? '-' : date('Y-m-s H:i:s', strtotime($item['first_visit_time']));
            return array_except($item, 'auth_time');
        }, $list);

        return $list;
    }
}