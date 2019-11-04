<?php

/**
 * 积分商城 搜索记录
 * Class QShop_SearchRecord
 */

class QShop_SearchRecord
{
    /**
     * @param $query
     * @param $source string order 或 product
     * @param $username
     * @return bool
     */
    public static function create(string $query, string $source, $username)
    {
        return DB::Insert("public.search_record", [
            "query" => DB::EscapeString($query),
            "username" => $username,
            "source" => $source,
        ]);
    }

    /**
     * @param $type string order 或 product
     * @param $username
     * @param int $limit
     * @return array
     */
    public static function latest($type, $username, int $limit = 20)
    {
        $sql = <<<SQL
         select query from 
         (select query, max(id) as id from public.search_record where username = '{$username}' and source = '{$type}' group by query) sr 
         order by id desc
         limit {$limit}
SQL;

        $list = (array) DBSLAVE::GetQueryResult($sql, false);
        return array_column($list, "query");
    }
}