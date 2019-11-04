<?php

/**
 * 管理员页面-活动任务管理 列表查询
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$activity_title = filter_input(INPUT_GET, 'activity_title', FILTER_SANITIZE_STRING);
$activity_type = filter_input(INPUT_GET, 'activity_type', FILTER_SANITIZE_STRING);
$state = filter_input(INPUT_GET, 'state', FILTER_SANITIZE_STRING);
$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_NUMBER_INT);


$select_params = [
    'eid' => $eid,
];

$list = QActivity_ReleaseManage::getActivityInfo($select_params);
$activity_list = QActivity_ReleaseManage::dealList(['activity_arr'=>[$list]]);

QActivity_ReleaseManage::display_result(['errorCode'=>0,'data'=>$activity_list[0]]);