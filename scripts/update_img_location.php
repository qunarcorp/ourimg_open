<?php
/**
 * 更新图片location统计
 * 05 01 * * *
 */
require_once __DIR__."/../htdocs/app_api.php";
ini_set('memory_limit', '1024M');
set_time_limit(0);

// 保证以单进程方式进行
crontab_run_one("crontab","update_img_location");

$syncTime = date("Y-m-d H:i:s");

function _log($msg)
{
    echo $msg . PHP_EOL;
    QLog::info("crontab", "update_img_location", $msg);
}
_log("<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<");
_log("更新开始" . $syncTime);
// 单次获取500数据
$take = 500;
$countSql = "SELECT count(1) as aggregate
            FROM public.img where is_del = 'f' and audit_state = 2 and place is not null";

$aggregate = DB::GetQueryResult($countSql)['aggregate'];
_log("本次共有{$aggregate}张图片location更新");
if ($aggregate) {
    $skip = 0;
    while ($skip < $aggregate){
        $listSql = "SELECT id, place, location
            FROM public.img where is_del = 'f' and audit_state = 2 and place is not null
            order by id asc limit {$take} offset {$skip}";
        $imgList = DB::GetQueryResult($listSql, false);
        foreach ($imgList as $img){
            $location = json_decode($img["location"], true);
            $city = $location["city"];//　城市
            $county = $location["county"];// 郡县
            $country = $location["country"];//　国家
            $province = $location["province"];// 省份
            if (! $country) {
                $insertCustomCitySql = <<<SQL
    insert into public.img_location (location_name, location_level, location, last_sync_time)
        values('{$img["place"]}', 'custom', null, now())
    ON CONFLICT (location_name, location_level)  DO UPDATE
        set last_sync_time = now()
    RETURNING id
SQL;
                $customCityId = pg_fetch_assoc(DB::Query($insertCustomCitySql))['id'];
                continue;
            }
            if ($country){
                // insert 国家
                _log("插入country: {$country}");
                $insertCountrySql = <<<SQL
    insert into public.img_location (location_name, location_level, location, last_sync_time)
        values('{$country}', 'country', '{"country": "{$country}"}', now())
    ON CONFLICT (location_name, location_level)  DO UPDATE
        set last_sync_time = now()
    RETURNING id
SQL;
                $countryId = pg_fetch_assoc(DB::Query($insertCountrySql))['id'];

                if ($province && $countryId) {
                    // insert 省份
                    _log("插入province: {$country}.{$province}");
                    $insertProvinceSql = <<<SQL
    insert into public.img_location (location_name, location_level, location, parent_id, last_sync_time)
        values('{$province}', 'province', '{"country":"{$country}","province":"{$province}"}', '{$countryId}', now())
    ON CONFLICT (location_name, location_level)  DO UPDATE
        set last_sync_time = now()
    RETURNING id
SQL;
                    $provinceId = pg_fetch_assoc(DB::Query($insertProvinceSql))['id'];
                    if ($city && $provinceId) {
                        // insert 城市
                        _log("插入city: {$country}.{$province}.{$city}");
                        $insertCitySql = <<<SQL
    insert into public.img_location (location_name, location_level, location, parent_id, last_sync_time)
        values('{$city}', 'city', '{"country":"{$country}","province":"{$province}","city":"{$city}"}', '{$provinceId}', now())
    ON CONFLICT (location_name, location_level)  DO UPDATE
        set last_sync_time = now()
    RETURNING id
SQL;
                        $cityId = pg_fetch_assoc(DB::Query($insertCitySql))['id'];
                        if ($county && $cityId) {
                            // insert 郡县
                            _log("插入county: {$country}.{$province}.{$city}.{$county}");
                            $updateCountySql = <<<SQL
    insert into public.img_location (location_name, location_level, location, parent_id, last_sync_time)
        values('{$county}', 'county', '{"country":"{$country}","province":"{$province}","city":"{$city}","county":"{$county}"}', '{$cityId}', now())
    ON CONFLICT (location_name, location_level)  DO UPDATE
        set last_sync_time = now()
    RETURNING id
SQL;
                            $countyId = pg_fetch_assoc(DB::Query($updateCountySql))['id'];
                        }
                    }
                }
            }
        }
        $skip += $take;
    }
    $delSql = <<<SQL
    delete from public.img_location where last_sync_time < '{$syncTime}'
SQL;

    $delRows = pg_affected_rows(DB::Query($delSql));
    _log("删除未更新location{$delRows}条");
}
_log("更新location成功");

_log("更新poi统计：开始");

$poiBaseSql = <<<SQL
    with poi_statistic as (
        select row_number() over() as id, place, count(1) as img_num from public.img where audit_state = 2 and is_del = 'f' and location->>'country' != '' group by place
    )
SQL;

// 单次获取500数据
$take = 500;
$countSql = "
    $poiBaseSql
    select count(1) as aggregate from poi_statistic
";

$poiAggregate = DB::GetQueryResult($countSql)['aggregate'];
_log("本次共有{$poiAggregate}poi更新");


if ($poiAggregate) {
    $skip = 0;
    while ($skip < $poiAggregate){
        $poiImgSql = "
            $poiBaseSql
            SELECT id, place, img_num
                from poi_statistic
                order by id asc limit {$take} offset {$skip}
            ";
        $poiImgList = DB::GetQueryResult($poiImgSql, false);
        foreach ($poiImgList as $poi){
            $insertPoiSql = <<<SQL
    insert into public.img_location (location_name, location_level, img_num, location, last_sync_time)
        values('{$poi["place"]}', 'poi', '{$poi["img_num"]}', null, now())
    ON CONFLICT (location_name, location_level)  DO UPDATE
        set img_num = '{$poi["img_num"]}', last_sync_time = now()
    RETURNING id
SQL;
            DB::Query($insertPoiSql);
        }
        $skip += $take;
    }
}
_log("更新poi统计：完成");
_log("更新location 图片数量：开始");
$updateLocationImgNumSql = <<<SQL
    update public.img_location il set img_num = (select count(1) from public.img i where i.location @> il.location and is_del = 'f' and audit_state = 2)
        where location_level not in ('custom', 'poi')
SQL;

DB::Query($updateLocationImgNumSql);
_log("更新location 图片数量：完成");

_log("更新自定义location 图片数量：开始");
$updateLocationImgNumSql = <<<SQL
    update public.img_location il set img_num = (select count(1) from public.img i where i.location->>'country' = '' and i.place = il.location_name and is_del = 'f' and audit_state = 2)
        where location_level = 'custom'
SQL;

DB::Query($updateLocationImgNumSql);
_log("更新自定义location 图片数量：完成");

$endTime = time();
_log(sprintf("脚本执行成功 %s, 共执行%s", date("Y-m-d H:i:s", $endTime), date('i:s',$endTime - strtotime($syncTime))));

_log(">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>");

