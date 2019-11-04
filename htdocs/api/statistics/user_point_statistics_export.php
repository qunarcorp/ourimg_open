<?php

/**
 * 积分数据统计
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allData = (new QStatistics_Points())->allData();

$allData = array_map(function($item){
    return array_only($item, [
        "name", "dept", "total_points", "current_points", "pass_points", "praise_points", "favorite_points",
        "task_points", "star_points", "delete_points", "exchange_points", "download_points"
    ]);
}, $allData);

$qExcelExport = new QExcel_Export("用户积分数据统计" . date("Y-m-d"), [
    "name" => "姓名",
    "dept" => "组织架构",
    "total_points" => "总积分",
    "current_points" => "剩余积分",
    "pass_points" => "上传积分",
    "praise_points" => "点赞积分",
    "favorite_points" => "收藏积分",
    "task_points" => "任务积分",
    "star_points" => "精选积分",
    "delete_points" => "删除积分",
    "exchange_points" => "兑换积分",
    "download_points" => "下载积分",
], [
    $allData
]);
$qExcelExport->export();