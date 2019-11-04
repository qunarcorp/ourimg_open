<?php

/**
 * 图片排行榜
 * Class QRank
 */

class QRank
{
    /**
     * upload rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function upload($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with upload_rank as (
        select row_number() over() as rank, * 
        from (select username, count(1) as aggregate 
            from public.img where audit_state = 2 and is_del = 'f' and username not in (select username from public.img where username not like '%.%' group by username)
             group by username order by aggregate desc, username asc
        ) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from upload_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate from upload_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), 'i.id desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * praise rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function praise($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with praise_rank as (
        select row_number() over() as rank, * 
        from (select username, sum(praise) as aggregate 
            from public.img where audit_state = 2 and is_del = 'f' and username not in (select username from public.img where username not like '%.%' group by username)
            group by username order by aggregate desc, username asc
        ) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from praise_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate from praise_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), 'i.praise desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * favorite rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function favorite($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with favorite_rank as (
        select row_number() over() as rank, * 
        from (select username, sum(favorite) as aggregate 
            from public.img where audit_state = 2 and is_del = 'f' and username not in (select username from public.img where username not like '%.%' group by username)
            group by username order by aggregate desc, username asc
        ) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from favorite_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate from favorite_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), 'i.favorite desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * download rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function download($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with download_rank as (
        select row_number() over() as rank, * 
        from (select username, sum(download) as aggregate 
            from public.img 
            where audit_state = 2 and is_del = 'f' and username not in (select username from public.img where username not like '%.%' group by username) 
            group by username order by aggregate desc, username asc) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from download_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate from download_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), 'i.download desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * browse rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function browse($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with browse_rank as (
        select row_number() over() as rank, * 
        from (select username, sum(browse) as aggregate 
            from public.img 
            where audit_state = 2 and is_del = 'f' and username not in (select username from public.img where username not like '%.%' group by username)
            group by username order by aggregate desc, username asc) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from browse_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate from browse_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), 'i.browse desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * point rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function point($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with point_rank as (
        select row_number() over() as rank, * 
        from (
            select username, total_points as aggregate 
            from public.system_user 
            where username not in (select username from public.img where username not like '%.%' group by username)
            order by total_points desc, username asc
        ) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from point_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate from point_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), 'i.id desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * popularity rank
     * @param int $pageSize
     * @param string $pageName
     * @return array
     */
    public static function popularity($pageSize = 20, $pageName = "page")
    {
        $bashSql = <<<SQL
    with popularity_rank as (
        select row_number() over() as rank, * from (
        select username, sum(favorite + praise) as aggregate, sum(favorite) as total_favorite, sum(praise) as total_praise 
        from public.img where audit_state = 2 and is_del = 'f' 
        and username not in (select username from public.img where username not like '%.%' group by username)
        group by username order by aggregate desc, username asc) tmp_rank
    )
SQL;
        $countSql = <<<SQL
    {$bashSql}
    select count(*) as aggregate from popularity_rank where aggregate > 0
SQL;
        $total = DB::GetQueryResult($countSql)["aggregate"];

        $currentPage = self::page($pageName);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $listSql = <<<SQL
    {$bashSql}
    select rank, username, aggregate, total_favorite, total_praise from popularity_rank where aggregate > 0 limit {$pageSize} offset {$offset}
SQL;

        $list = self::rankUserInfo(DB::GetQueryResult($listSql, false), '(i.favorite + i.praise) desc');

        return compact("total", "lastPage", "pageSize", "currentPage", "list");
    }

    /**
     * 获取当前页码
     * @param string $pageName
     * @return mixed
     */
    private static function page($pageName = "page")
    {
        return max(1, intval($_GET[$pageName]));
    }

    /**
     * rank user info
     * @param $list
     * @param null $orderBy
     * @return array
     */
    private static function rankUserInfo($list, $orderBy = null)
    {
        $usernameStr = array2insql(array_column($list, "username"));

        $orderBy = is_null($orderBy) ? "" : " order by {$orderBy} ";
        $sql = <<<SQL
    select username, name as real_name, img as avatar, 
    (select array_agg(img_info) from (select (url || '|' || width || '|' || height) as img_info from public.img i where i.username = su.username and i.audit_state = 2 and i.is_del = 'f' {$orderBy} limit 5) user_img) as img_list 
    from public.system_user su where username in ({$usernameStr});
SQL;

        $userInfo = array_replace_key(DB::GetQueryResult($sql, false), "username");

        return array_map(function($row) use ($userInfo){
            $userInfo = $userInfo[$row["username"]];
            $userInfo["img_list"] = trim(trim($userInfo["img_list"], "{"), "}");

            $imgList = empty($userInfo["img_list"]) ? [] : array_map(function($item){
                global $system_domain;
                list($imgPath, $width, $height) = explode("|", $item);
                return QImg::getImgUrlResize([
                    "img" => $imgPath,
                    "width" => $width,
                    "height" => $height,
                    "r_width" => 100,
                    "r_height" => 100,
                    'system_domain' => $system_domain,
                    "in" => "inner_domain"
                ]);
            }, explode(",", $userInfo["img_list"]));

            return array_merge($row, [
                "img_list" => $imgList,
                "real_name" => $userInfo["real_name"],
                "avatar" => $userInfo["avatar"],
            ]);
        }, $list);

    }
}


