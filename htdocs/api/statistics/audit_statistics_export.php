<?php

/**
 * 审核数据统计导出
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allData = (new QStatistics_ImgAudit())->allData();

$allData = array_map(function($item){
    return array_only($item, [
        "name", "dept", "audit_num", "pass_num", "reject_num", "week_audit_num", "week_pass_num", "week_reject_num"
    ]);
}, $allData);

$qExcelExport = new QExcel_Export("用户审核数据统计" . date("Y-m-d"), [
    "name" => "姓名",
    "dept" => "组织架构",
    "audit_num" => "审核总量",
    "pass_num" => "审核通过量",
    "reject_num" => "驳回量",
    "week_audit_num" => "本周审核总量",
    "week_pass_num" => "本周审核通过量",
    "week_reject_num" => "本周驳回量",
], [
    $allData
]);
$qExcelExport->export();