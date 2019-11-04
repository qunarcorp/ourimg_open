<?php

/**
 * 统计:图片状态 导出
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allData = (new QStatistics_Img())->allData();

$allData = array_map(function($item){
    return array_only($item, [
        "name", "dept", "all_img_num", "del_num", "to_submit_num",
        "check_pending_num", "pass_num", "reject_num"
    ]);
}, $allData);

$qExcelExport = new QExcel_Export("用户图片数据统计" . date("Y-m-d"), [
    "name" => "姓名",
    "dept" => "组织架构",
    "all_img_num" => "上传总量",
    "del_num" => "已删除",
    "to_submit_num" => "草稿箱",
    "check_pending_num" => "待审核",
    "pass_num" => "已通过",
    "reject_num" => "未通过",
], [
    $allData
]);
$qExcelExport->export();