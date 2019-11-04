<?php

/**
 * 统计:用户交互统计 导出
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allData = (new QStatistics_Interaction())->allData();

$allData = array_map(function($item){
    return array_only($item, [
        "name", "dept", "interaction_people", "browse_num", "praise_num", "favorite_num", "download_num",
    ]);
}, $allData);

$qExcelExport = new QExcel_Export("用户交互数据统计" . date("Y-m-d"), [
    "name" => "姓名",
    "dept" => "组织架构",
    "interaction_people" => "交互用户数",
    "browse_num" => "浏览图片量",
    "praise_num" => "点赞图片量",
    "favorite_num" => "收藏图片量",
    "download_num" => "下载图片量",
], [
    $allData
]);
$qExcelExport->export();