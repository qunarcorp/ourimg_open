<?php

/**
 * 涉及poi相关统计
 * Class QStatistics_Location
 */

class QStatistics_Location
{
    /**
     * img statistic
     * @return array
     */
    public function imgStatistic()
    {
        $sql = <<<SQL
    select location_level, count(1) as aggregate
    from public.img_location
    group by location_level
SQL;
        $imgLocation = DBSLAVE::GetQueryResult($sql, false);

        $carry = array_reduce($imgLocation, function($carry, $item){
            $carry[$item["location_level"]] = $item["aggregate"];
            return $carry;
        });

        return [
            "city" => (int) $carry["city"],
            "custom" => (int) $carry["custom"],
            "poi" => (int) $carry["poi"],
            "province" => (int) $carry["province"],
            "country" => (int) $carry["country"],
            "county" => (int) $carry["county"],
        ];
    }

    /**
     * img num
     * @param string $level
     * @param int $pid
     * @param int $page
     * @param int $prePage
     * @return array
     */
    public function imgNum($level = 'country', $pid = 0, $page = 1, $prePage = 50)
    {
        $parentSql = (($level == 'country' && $pid == 0) || $pid != 0) ? " and parent_id = '{$pid}' " : "";

        $countSql = <<<SQL
    select count(1) as aggregate
        from public.img_location
        where location_level = '{$level}' {$parentSql}
SQL;
        $total = (int) DBSLAVE::GetQueryResult($countSql)["aggregate"];
        $lastPage = ceil($total / $prePage);
        $offset = ($page - 1) * $prePage;


        $listSql = <<<SQL
    select id, location_name, location_level, img_num, location, 
        case when location_level = 'country' then 'province' 
            when location_level = 'province' then 'city' 
            when location_level = 'city' then 'county' 
            else '' end
            as child_level
        from public.img_location
        where location_level = '{$level}' {$parentSql}
        order by img_num desc, id desc 
        limit {$prePage} offset {$offset}
SQL;

        $list = (array) DBSLAVE::GetQueryResult($listSql, false);

        $list = array_map(function($item){
            $location = $item["location"] ? json_decode($item["location"], true) : [];
            return array_merge($item, [
                "location" => array_merge([
                    "country" => "",
                    "province" => "",
                    "city" => ""
                ], array_only(
                    $location,
                    [
                        "country", "province", "city"
                    ]
                ))
            ]);
        }, $list);

        return compact("total", "lastPage", "list", "page");
    }

    public function allData()
    {
        $listSql = <<<SQL
    select location_name, location_level, img_num
        from public.img_location
        order by img_num desc 
SQL;
        $list = (array) DBSLAVE::GetQueryResult($listSql, false);
        return array_group($list, 'location_level');
    }
}