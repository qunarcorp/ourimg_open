<?php

/**
 * 图片相关统计
 * Class QStatistics_Img
 */

class QStatistics_Img
{
    /**
     * 图片统计数据
     * @return array
     */
    public function status()
    {
        $sql = <<<SQL
        select 
            count(1) as all_img_num,
            sum(case when is_del = 't' then 1 else 0 end) as del_num,
            sum(case when audit_state = 0 and is_del = 'f' then 1 else 0 end) as to_submit_num,
            sum(case when audit_state = 1 and is_del = 'f' then 1 else 0 end) as check_pending_num,
            sum(case when audit_state = 2 and is_del = 'f' then 1 else 0 end) as pass_num,
            sum(case when audit_state = 3 and is_del = 'f' then 1 else 0 end) as reject_num
        from public.img
SQL;
        $statistic = DBSLAVE::GetQueryResult($sql);

        return [
            "all_img_num" => (int) $statistic["all_img_num"],
            "del_num" => (int) $statistic["del_num"],
            "to_submit_num" => (int) $statistic["to_submit_num"],
            "check_pending_num" => (int) $statistic["check_pending_num"],
            "pass_num" => (int) $statistic["pass_num"],
            "reject_num" => (int) $statistic["reject_num"],
        ];
    }

    /**
     * paginate
     * @param int $pageSize
     * @param int $page
     * @param $tabType
     * @param $sortField
     * @param $sortOrder
     * @return array
     */
    public function paginate($pageSize = 20, $page = 1, $tabType, $sortField, $sortOrder, $query)
    {
        $baseSql = $this->getBaseSql();

        $where = [];

        if ($query) {
            $where[] = " ( su.username ~ '{$query}' or su.name ~ '{$query}' ) ";
        }

        if ($tabType == 'to_submit') {
            $where[] = ' ui.to_submit_num > 0 ';
        }elseif ($tabType == 'check_pending') {
            $where[] = ' ui.check_pending_num > 0 ';
        }elseif ($tabType == 'pass') {
            $where[] = ' ui.pass_num > 0 ';
        }elseif ($tabType == 'reject') {
            $where[] = ' ui.reject_num > 0 ';
        }elseif ($tabType == 'del') {
            $where[] = ' ui.del_num > 0 ';
        }

        if (! empty($where)) {
            $whereSql = " where " . implode(" and ", $where);
        }else{
            $whereSql = "";
        }

        $countSql = <<<SQL
    {$baseSql}
    select count(1) as aggregate from user_img ui
    left join public.system_user su on su.username = ui.username 
    {$whereSql}
SQL;

        if (in_array($sortField , ["all_img_num", "to_submit_num", "check_pending_num", "pass_num", "reject_num", "del_num"])) {
            $sort = $sortOrder == "descend" ? 'desc' : 'asc';
            $order = " ui.{$sortField} {$sort} ";
        }else{
            $order = " ui.all_img_num desc ";
        }

        $total = DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $lastPage = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;
        $listSql = <<<SQL
    {$baseSql}
    select su.name, ui.username, su.dept, ui.all_img_num, ui.del_num, ui.to_submit_num, ui.check_pending_num, 
        ui.pass_num, ui.reject_num, ui.earliest_upload_time
    from user_img ui left join public.system_user su on su.username = ui.username 
    {$whereSql}
    order by {$order}
    limit {$pageSize} offset {$offset}
SQL;

        $list = DBSLAVE::GetQueryResult($listSql, false);
        $list = array_map(function($item){
            $item["dept"] = implode("/", array_filter(json_decode($item["dept"], true), function($item){
                return $item;
            }));
            return $item;
        }, $list);

        return compact("total", "lastPage", "page", "pageSize", "list");
    }

    private function getBaseSql()
    {
        $baseSql = <<<SQL
        with user_img as (
            select 
                i.username,
                count(1) as all_img_num,
                sum(case when i.is_del = 't' then 1 else 0 end) as del_num,
                sum(case when i.audit_state = 0 and i.is_del = 'f' then 1 else 0 end) as to_submit_num,
                sum(case when i.audit_state = 1 and i.is_del = 'f' then 1 else 0 end) as check_pending_num,
                sum(case when i.audit_state = 2 and i.is_del = 'f' then 1 else 0 end) as pass_num,
                sum(case when i.audit_state = 3 and i.is_del = 'f' then 1 else 0 end) as reject_num,
                (select to_char(create_time, 'yyyy-mm-dd hh24:mi:ss') from public.img ii where ii.username = i.username order by create_time asc limit 1) as earliest_upload_time
            from public.img i
            where i.username is not null and (i.username like '%.%' or i.username = 'piao_qsight_provider')
            group by i.username
        )
SQL;
        return $baseSql;
    }

    public function allData()
    {
        $baseSql = $this->getBaseSql();

        $listSql = <<<SQL
    {$baseSql}
    select su.name, ui.username, su.dept, ui.all_img_num, ui.del_num, ui.to_submit_num, ui.check_pending_num, 
        ui.pass_num, ui.reject_num, ui.earliest_upload_time
    from user_img ui left join public.system_user su on su.username = ui.username 
    order by ui.earliest_upload_time desc
SQL;

        $list = DBSLAVE::GetQueryResult($listSql, false);
        $list = array_map(function($item){
            $item["dept"] = implode("/", array_filter(json_decode($item["dept"], true), function($item){
                return $item;
            }));
            return $item;
        }, $list);

        return $list;
    }
}