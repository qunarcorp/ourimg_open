<?php

/**
 * 交互相关统计
 * Class QStatistics_Interaction
 *
 */

class QStatistics_Interaction
{
    /**
     * user list
     * @param int $pageSize
     * @param int $page
     * @param $tabType
     * @param $sortField
     * @param $sortOrder
     * @return array
     */
    public function userList($pageSize = 10, $page = 1, $tabType, $sortField, $sortOrder, $query)
    {
        $baseSql = $this->getBaseSql();

        $where = [
            ' (browse_interaction_people + praise_interaction_people + favorite_interaction_people + download_interaction_people) > 0 '
        ];


        if ($tabType == 'browse_num') {
            $where[] = ' browse_num > 0 ';
        } elseif ($tabType == 'praise_num') {
            $where[] = ' praise_num > 0 ';
        } elseif ($tabType == 'favorite_num') {
            $where[] = ' favorite_num > 0 ';
        } elseif ($tabType == 'download_num') {
            $where[] = ' download_num > 0 ';
        }

        if ($query) {
            $where[] = " ( username ~ '{$query}' or name ~ '{$query}' ) ";
        }

        if (!empty($where)) {
            $whereSql = " where " . implode(" and ", $where);
        } else {
            $whereSql = "";
        }

        if (in_array($sortField, ["interaction_people", "browse_num", "praise_num", "favorite_num", "download_num"])) {
            $sort = $sortOrder == "descend" ? 'desc' : 'asc';
            $order = " {$sortField} {$sort} ";
        } else {
            $order = " (browse_interaction_people + praise_interaction_people + favorite_interaction_people + download_interaction_people) desc ";
        }

        $countSql = <<<SQL
    {$baseSql}
    select count(1) as aggregate from user_interaction
    {$whereSql}
SQL;

        $total = (int)DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $page = (int)$page;
        $pageSize = (int)$pageSize;
        $lastPage = ceil($total / $pageSize);
        $offset = ($page - 1) * $pageSize;

        $listSql = <<<SQL
    {$baseSql}
    select 
        id, username, name, dept,
        (browse_interaction_people + praise_interaction_people + favorite_interaction_people + download_interaction_people) as interaction_people,
        browse_num, praise_num, favorite_num, download_num
    from user_interaction
    {$whereSql}
    order by {$order}
    limit {$pageSize} offset {$offset}
SQL;
        $list = DBSLAVE::GetQueryResult($listSql, false);
        $list = array_map(function ($item) {
            $item["dept"] = implode("/", array_filter(json_decode($item["dept"], true), function ($item) {
                return $item;
            }));
            return $item;
        }, $list);

        return compact("total", "page", "pageSize", "lastPage", "list");
    }

    public function statistics()
    {
        $baseSql = $this->getBaseSql();
        $statisticsSql = <<<SQL
    {$baseSql}
    select 
        sum(browse_num) as total_browse_num, sum(praise_num) as total_praise_num,
        sum(favorite_num) as total_favorite_num, sum(download_num) as total_download_num
    from user_interaction
SQL;
        $list = DBSLAVE::GetQueryResult($statisticsSql, true);

        return $list;
    }

    private function getBaseSql()
    {
        $baseSql = <<<SQL
    with user_interaction as (
        select su.id, su.username, su.name, su.dept,
            (
                select count(*) from (
                    select i.username from public.browse_trace bt 
                    left join public.img i on bt.img_id = i.id 
                    where bt.username = su.username
                    group by i.username
                ) tmp_browse
            ) as browse_interaction_people,
            (
                select count(*) from (
                    select i.username from public.praise p 
                    left join public.img i on p.img_id = i.id 
                    where p.username = su.username
                    group by i.username
                ) tmp_praise
            ) as praise_interaction_people,
            (
                select count(*) from (
                    select i.username from public.favorite f 
                    left join public.img i on f.img_id = i.id 
                    where f.username = su.username
                    group by i.username
                ) tmp_favorite
            ) as favorite_interaction_people,
            (
                select count(*) from (
                    select i.username from public.download_history dh 
                    left join public.img i on dh.img_id = i.id 
                    where dh.username = su.username
                    group by i.username
                ) tmp_download
            ) as download_interaction_people,
            (
                select count(*) from public.browse_trace bt2 
                    where bt2.username = su.username
            ) as browse_num,
            (
                select count(*) from public.praise p2 
                    where p2.username = su.username
            ) as praise_num,
            (
                select count(*) from public.favorite f2 
                    where f2.username = su.username
            ) as favorite_num,
            (
                select count(*) from public.download_history dh2 
                    where dh2.username = su.username
            ) as download_num
        from public.system_user su
        where su.username like '%.%' or su.username = 'piao_qsight_provider'
    )
SQL;
        return $baseSql;
    }

    public function allData()
    {
        $baseSql = $this->getBaseSql();

        $listSql = <<<SQL
    {$baseSql}
    select 
        id, username, name, dept,
        (browse_interaction_people + praise_interaction_people + favorite_interaction_people + download_interaction_people) as interaction_people,
        browse_num, praise_num, favorite_num, download_num
    from user_interaction
    order by (browse_interaction_people + praise_interaction_people + favorite_interaction_people + download_interaction_people) desc 
SQL;
        $list = DBSLAVE::GetQueryResult($listSql, false);
        $list = array_map(function ($item) {
            $item["dept"] = implode("/", array_filter(json_decode($item["dept"], true), function ($item) {
                return $item;
            }));
            return $item;
        }, $list);

        return $list;
    }
}

