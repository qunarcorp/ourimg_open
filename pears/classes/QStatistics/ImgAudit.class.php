<?php

/**
 * 图片审核相关统计
 * Class QStatistics_ImgAudit
 */

class QStatistics_ImgAudit
{
    /**
     * audit user list
     * @param int $pageSize
     * @param int $page
     * @param $tabType
     * @param $sortField
     * @param $sortOrder
     * @param $query
     * @return array
     */
    public function auditUserList($pageSize = 10, $page = 1, $tabType, $sortField, $sortOrder, $query)
    {
        $baseSql = $this->getBaseSql();

        $where = [];
        if ($tabType == 'audit_num') {
            $where[] = ' ad.audit_num > 0 ';
        }elseif ($tabType == 'pass_num') {
            $where[] = ' ad.pass_num > 0 ';
        }elseif ($tabType == 'reject_num') {
            $where[] = ' ad.reject_num > 0 ';
        }elseif ($tabType == 'week_audit_num') {
            $where[] = ' ad.week_audit_num > 0 ';
        }elseif ($tabType == 'week_pass_num') {
            $where[] = ' ad.week_pass_num > 0 ';
        }elseif ($tabType == 'week_reject_num') {
            $where[] = ' ad.week_reject_num > 0 ';
        }

        if ($query) {
            $where[] = " ( su.username ~ '{$query}' or su.name ~ '{$query}' ) ";
        }

        if (! empty($where)) {
            $whereSql = " where " . implode(" and ", $where);
        }else{
            $whereSql = "";
        }

        if (in_array($sortField , ["audit_num", "pass_num", "reject_num", "week_audit_num", "week_pass_num", "week_reject_num"])) {
            $sort = $sortOrder == "descend" ? 'desc' : 'asc';
            $order = " ad.{$sortField} {$sort} ";
        }else{
            $order = " ad.audit_num desc ";
        }

        $countSql = <<<SQL
    {$baseSql}
    select count(1) as aggregate 
        from audit_data ad
        left join public.system_user su on su.username = ad.audit_user
        {$whereSql}
SQL;
        $total = (int) DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $page = (int) $page;
        $pageSize = (int) $pageSize;
        $lastPage = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;

        $listSql = <<<SQL
    {$baseSql}
    select ad.audit_user as username, 
        case when ad.audit_user = '系统审核' then '系统审核' else su.name end as name,
        case when ad.audit_user = '系统审核' then '["去哪儿网"]' else su.dept end as dept,
        ad.audit_num, ad.pass_num, ad.reject_num, ad.offline_num, 
        ad.week_audit_num, ad.week_pass_num, ad.week_reject_num, ad.week_offline_num
        from audit_data ad
        left join public.system_user su on su.username = ad.audit_user
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

        return compact("total", "page", "pageSize", "lastPage", "list");
    }

    private function getBaseSql()
    {
        $baseSql = <<<SQL
    with audit_data as (
        select audit_user, count(1) as audit_num, 
            sum(case when audit_state = 2 then 1 else 0 end) as pass_num,
            sum(case when audit_state = 3 then 1 else 0 end) as reject_num,
            sum(case when audit_state = 4 then 1 else 0 end) as offline_num,
            sum(case when audit_time >= (CURRENT_DATE-(EXTRACT(DOW FROM CURRENT_DATE)-1||'day')::interval) then 1 else 0 end) as week_audit_num,
            sum(case when audit_state = 2 and audit_time >= (CURRENT_DATE-(EXTRACT(DOW FROM CURRENT_DATE)-1||'day')::interval) then 1 else 0 end) as week_pass_num,
            sum(case when audit_state = 3 and audit_time >= (CURRENT_DATE-(EXTRACT(DOW FROM CURRENT_DATE)-1||'day')::interval) then 1 else 0 end) as week_reject_num,
            sum(case when audit_state = 4 and audit_time >= (CURRENT_DATE-(EXTRACT(DOW FROM CURRENT_DATE)-1||'day')::interval) then 1 else 0 end) as week_offline_num
        from public.img 
        where audit_state in (2, 3, 4) and is_del = 'f' and audit_user like '%.%'
        group by audit_user
    )
SQL;
        return $baseSql;
    }

    public function auditStatistics()
    {
        $baseSql = $this->getBaseSql();

        $statisticsSql = <<<SQL
    {$baseSql}
    select 
        sum(audit_num) as total_audit_num, sum(pass_num) as total_pass_num, sum(reject_num) as total_reject_num,
        sum(week_audit_num) as total_week_audit_num, sum(week_pass_num) as total_week_pass_num, sum(week_reject_num) as total_week_reject_num 
    from audit_data
SQL;
        $statistics = DBSLAVE::GetQueryResult($statisticsSql, true);

        return $statistics;
    }

    public function allData()
    {
        $baseSql = $this->getBaseSql();

        $listSql = <<<SQL
    {$baseSql}
    select ad.audit_user as username, 
        case when ad.audit_user = '系统审核' then '系统审核' else su.name end as name,
        case when ad.audit_user = '系统审核' then '["去哪儿网"]' else su.dept end as dept,
        ad.audit_num, ad.pass_num, ad.reject_num, ad.offline_num, 
        ad.week_audit_num, ad.week_pass_num, ad.week_reject_num, ad.week_offline_num
        from audit_data ad
        left join public.system_user su on su.username = ad.audit_user
        order by ad.audit_num desc
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