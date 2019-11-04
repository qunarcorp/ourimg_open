<?php

/**
 * 积分统计
 * Class QStatistics_Points
 */

class QStatistics_Points
{
    /**
     * all point
     * @return array
     */
    public function allPoint()
    {
        $totalPointsSql = <<<SQL
    select sum(total_points) as total_points, sum(current_points) as current_points 
        from public.system_user su
        where su.username like '%.%'
SQL;
        $totalPointsResult = DBSLAVE::GetQueryResult($totalPointsSql);

        $pointsTraceSql = <<<SQL
    select 
        sum(case when operate_source ~ 'pass' then abs(change_points) else 0 end) as pass_points,
        sum(case when operate_source = 'praise' then abs(change_points) else 0 end) as praise_points,
        sum(case when operate_source = 'favorite' then abs(change_points) else 0 end) as favorite_points,
        sum(case when operate_source ~ 'task' then abs(change_points) else 0 end) as task_points,
        0 as star_points,
        sum(case when operate_source ~ 'delete' then abs(change_points) else 0 end) as delete_points,
        sum(case when operate_source = 'exchange' then abs(change_points) else 0 end) as exchange_points,
        sum(case when operate_source = 'download' then abs(change_points) else 0 end) as download_points
    from public.points_trace
SQL;

        $pointsTraceResult = DBSLAVE::GetQueryResult($pointsTraceSql);

        return [
            "total_points" => (int) $totalPointsResult["total_points"],
            "current_points" => (int) $totalPointsResult["current_points"],
            "pass_points" => (int) $pointsTraceResult["pass_points"],
            "praise_points" => (int) $pointsTraceResult["praise_points"],
            "favorite_points" => (int) $pointsTraceResult["favorite_points"],
            "task_points" => (int) $pointsTraceResult["task_points"],
            "star_points" => (int) $pointsTraceResult["star_points"],
            "delete_points" => (int) $pointsTraceResult["delete_points"],
            "exchange_points" => (int) $pointsTraceResult["exchange_points"],
            "download_points" => (int) $pointsTraceResult["download_points"],
        ];
    }

    /**
     * user point list
     * @param int $pageSize
     * @param int $page
     * @return array
     */
    public function userPointList($pageSize = 10, $page = 1, $tabType, $sortField, $sortOrder, $query)
    {
        $baseSql = $this->getBaseSql();

        $where = [
            " su.username like '%.%' "
        ];

        if (in_array($tabType, [
            'pass_points', 'praise_points', 'favorite_points', 'task_points', 'star_points',
            'delete_points', 'exchange_points', 'download_points'
        ])) {
            $where[] = " up.{$tabType} > 0 ";
        }elseif($tabType == 'current_points'){
            $where[] = " su.current_points > 0 ";
        }else{
            $where[] = " su.total_points > 0 ";
        }

        if ($query) {
            $where[] = " ( su.username ~ '{$query}' or su.name ~ '{$query}' ) ";
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
        left join user_point up on up.username = su.username
        {$whereSql}
SQL;

        $total = (int) DBSLAVE::GetQueryResult($totalSql)["aggregate"];

        if (in_array($sortField , ['total_points', 'pass_points', 'praise_points', 'favorite_points', 'task_points', 'star_points',
            'current_points', 'delete_points', 'exchange_points', 'download_points'])) {
            $sort = $sortOrder == "descend" ? 'desc' : 'asc';
            $order = in_array($sortField, ['total_points', 'current_points']) ? " su.{$sortField} {$sort} " : " up.{$sortField} {$sort} ";
        }else{
            $order = " su.total_points desc ";
        }

        $page = (int) $page;
        $pageSize = (int) $pageSize;
        $lastPage = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;

        $listSql = <<<SQL
    {$baseSql}
    select up.username, su.name, su.total_points, su.current_points, su.dept, up.pass_points, up.praise_points, 
        up.favorite_points, up.task_points, up.star_points, up.delete_points, up.exchange_points, up.download_points
        from public.system_user su 
        left join user_point up on up.username = su.username
        {$whereSql}
        order by {$order} limit {$pageSize} offset {$offset}
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
        with user_point as (
            select su.username, 
                sum(case when operate_source ~ 'pass' then abs(change_points) else 0 end) as pass_points,
                sum(case when operate_source = 'praise' then abs(change_points) else 0 end) as praise_points,
                sum(case when operate_source = 'favorite' then abs(change_points) else 0 end) as favorite_points,
                sum(case when operate_source ~ 'task' then abs(change_points) else 0 end) as task_points,
                sum(case when operate_source ~ 'recommend' then abs(change_points) else 0 end) as star_points,
                sum(case when operate_source ~ 'delete' then abs(change_points) else 0 end) as delete_points,
                sum(case when operate_source = 'exchange' then abs(change_points) else 0 end) as exchange_points,
                sum(case when operate_source = 'download' then abs(change_points) else 0 end) as download_points
            from public.system_user su
            left join public.points_trace pt on pt.username = su.username
            group by su.username
    )
SQL;
        return $baseSql;
    }

    public function allData()
    {
        $baseSql = $this->getBaseSql();
        $listSql = <<<SQL
    {$baseSql}
    select up.username, su.name, su.dept, su.total_points, su.current_points, up.pass_points, up.praise_points, 
        up.favorite_points, up.task_points, up.star_points, up.delete_points, up.exchange_points, up.download_points
        from public.system_user su 
        left join user_point up on up.username = su.username
        where su.username like '%.%' 
        order by  su.total_points desc 
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