<?php

/**
 * 图片关键字
 * Class QImgKeyword
 */

class QImgKeyword
{
    /**
     * push keyword
     * @param $keywords
     */
    public static function push($keywords)
    {
        $keywords = array_filter($keywords);
        if (! empty($keywords)) {
            $keywordStr = implode(", ", array_map(function($item){
                return "('{$item}')";
            }, $keywords));

            $insertSql = <<<SQL
insert into public.img_keyword (keyword) (SELECT * FROM (VALUES {$keywordStr}) v(keyword))
    ON CONFLICT (keyword)  DO NOTHING
SQL;

            DB::Query($insertSql);
        }
    }

    /**
     * keyword suggest
     * @param $search
     * @return array
     */
    public static function suggest($search)
    {
        $search = (string) $search;

        $where = "";
        if (! empty($search)) {
            $where = " where keyword ~ '{$search}' ";
        }

        $suggestSql = <<<SQL
select keyword from public.img_keyword
    {$where}
    limit 20
SQL;

        $keywordList = (array) DBSLAVE::GetQueryResult($suggestSql, false);

        return array_column($keywordList, "keyword");
    }
}