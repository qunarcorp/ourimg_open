<?php

/**
 * 用户数据统计 导出
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allData = (new QStatistics_UserStatistics())->allData();

$allData = array_map(function($item){
    return array_only($item, [
        "name", "dept", "first_visit_time", "img_num", "earliest_upload_time", "auth_date"
    ]);
}, $allData);

$qExcelExport = new QExcel_Export("用户数据统计" . date("Y-m-d"), [
    "name" => "姓名",
    "dept" => "组织架构",
    "first_visit_time" => "首次访问时间",
    "img_num" => "上传图片总数",
    "earliest_upload_time" => "首次上传时间",
    "auth_date" => "授权时间",
], [
    $allData
]);
$qExcelExport->export();