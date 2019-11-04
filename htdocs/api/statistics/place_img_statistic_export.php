<?php

/**
 * 拍摄地点统计导出
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allData = (new QStatistics_Location())->allData();

$allData = array_map(function($item){
    return array_map(function($row){
        return array_only($row, [
            "location_name", "img_num"
        ]);
    }, $item);
}, $allData);

$qExcelExport = new QExcel_Export("拍摄地点覆盖统计" . date("Y-m-d"), [
    "location_name" => "拍摄地点",
    "img_num" => "图片数量",
], $allData);

$qExcelExport->export();