<?php

/**
 * 任务完成情况接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

//获取当前任务列表
$task_arr = [];

//上传任务
//获取用户正在完成的任务
$params_upload = [
    'task_name' => 'upload',
];
$upload_task_rs = QImgTask::getUserTasks($params_city);
$task_rs = $upload_task_rs[0];
if( $task_rs ){
    $task_arr[] = [
        'complete_state' => $task_rs['complete_state'],
        'task_name' => $task_rs['task_name'],
        'complete_num' => $task_rs['complete_num'],
    ];
}else{
    $task_arr[] = [
        'complete_state' => 'undo',
        'task_name' => 'upload',
        'complete_num' => 0,
    ];
}

//城市任务

//获取城市
$city_sql = " SELECT * FROM ". QImgSearch::$taskCityTableName 
        ." WHERE begin_time <= now() AND end_time >= now() ORDER BY end_time ASC ";
$city_rs = DB::GetQueryResult($city_sql, false);
$city_arr = array_column($city_rs, 'city_name');
$city_end_time = date("Y-m-d", strtotime($city_rs[0]['end_time']));


//获取用户正在完成的任务
$params_city = [
    'task_name' => 'city',
];
$city_task_rs = QImgTask::getUserTasks($params_city);
$task_rs = $city_task_rs[0];
if( $task_rs ){
    $task_arr[] = [
        'complete_state' => $task_rs['complete_state'],
        'task_name' => $task_rs['task_name'],
        'city_arr' => $city_arr,
        'city_end_time' => $city_end_time,
    ];
}else{
    $task_arr[] = [
        'complete_state' => 'undo',
        'task_name' => 'city',
        'city_arr' => $city_arr,
        'city_end_time' => $city_end_time,
    ];
}



$rs = [
    "status" => 0,
    "message" => "查询成功",
    "task_info" => $task_arr,
];
display_json_str_common($rs, $callback);
