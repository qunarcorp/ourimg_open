<?php

/**
 * 管理员页面-活动任务管理 发布任务
 */
require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);
session_write_close();//关闭session

$postParams = json_input();

$eid = filter_var($postParams["eid"], FILTER_SANITIZE_NUMBER_INT) ?: "";//活动eid-int

$activity_info = QActivity_ReleaseManage::checkState(['new_state'=>'online', 'eid'=>$eid]);
QActivity_ReleaseManage::checkBeginTime($activity_info);
QActivity_ReleaseManage::online(['eid'=>$eid]);