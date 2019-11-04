<?php

/**
 * 管理员页面-活动任务管理 保存活动内容
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);
session_write_close();//关闭session

$postParams = json_input();

$activity_title = trim(filter_var($postParams["activity_title"], FILTER_SANITIZE_STRING) ?: "");//活动名称-string
$state = trim(filter_var($postParams["state"], FILTER_SANITIZE_STRING) ?: "");//活动状态-string
$activity_type = $postParams['activity_type'];//活动类型--array
$city_sights = $postParams['city_sights'];//城市景点--array
$theme_keywords = $postParams['theme_keywords'];//主题关键字--array
$activity_introduction = $postParams['activity_introduction'];//活动介绍--text
$activity_description = $postParams['activity_description'];//活动说明--text
$activity_reward = $postParams['activity_reward'];//活动奖励--text
$img_requirements = $postParams['img_requirements'];//图片要求--text
$img_upload_points = filter_var($postParams["img_upload_points"], FILTER_SANITIZE_NUMBER_INT) ?: "";//图片上传积分-int
$task_points = filter_var($postParams["task_points"], FILTER_SANITIZE_NUMBER_INT) ?: "";//任务积分-int
$need_img_count = filter_var($postParams["need_img_count"], FILTER_SANITIZE_NUMBER_INT) ?: "";//需要图片基数-int
$points_cycle = trim(filter_var($postParams["points_cycle"], FILTER_SANITIZE_STRING) ?: "");//任务奖励周期-string
$points_time_type = trim(filter_var($postParams["points_time_type"], FILTER_SANITIZE_STRING) ?: "");//积分发放节点-string
$background_img = trim(filter_var($postParams["background_img"], FILTER_SANITIZE_URL) ?: "");//背景图展示-string
$begin_time = $postParams["begin_time"] ? date("Y-m-d", strtotime($postParams["begin_time"])) . ' 00:00:00' : "";//活动开始时间-string
$end_time = $postParams["end_time"] ? date("Y-m-d", strtotime($postParams["end_time"])) . ' 23:59:59' : "";//活动结束时间-string


//验证字段信息
QActivity_ReleaseManage::paramsValidator($postParams);
$current_time = date("Y-m-d H:i:s");

//更新活动表
$insert_info = [
    'activity_title' => $activity_title,
    'activity_type' => "{".implode(",",$activity_type)."}",
    'city_sights' => json_encode($city_sights, true),
    'theme_keywords' => "{".implode(",",$theme_keywords)."}",
    'img_upload_points' => $img_upload_points,
    'task_points' => $task_points,
    'need_img_count' => $need_img_count,
    'points_cycle' => $points_cycle,
    'points_time_type' => $points_time_type,
    'activity_introduction' => $activity_introduction,
    'activity_description' => $activity_description,
    'activity_reward' => $activity_reward,
    'img_requirements' => $img_requirements,
    'background_img' => $background_img,
    'begin_time' => $begin_time,
    'end_time' => $end_time,
    'state' => $state ? $state : 'pending',
    'now_img_counts' => 0,
    'create_username' => $login_user_name,
    'update_username' => $login_user_name,
    'create_time' => $current_time,
    'update_time' => $current_time,
];

//增加发布时间
if( $state == 'online' ){
    $insert_info['release_time'] = $current_time;
}

$insert_rs = QActivity_ReleaseManage::insert(['insert_info'=>$insert_info]);
if( !$insert_rs ){
    QActivity_ReleaseManage::display_result(['errorCode'=>201]);
}

//更新eid

$eid = QActivity_ReleaseManage::getEid(['id'=>$insert_rs]);
$eid_update = [
    'eid' => $eid,
];
$update_rs = DB::Update(QImgSearch::$activityTasksTableName, $insert_rs, $eid_update, 'id');
if( !$update_rs ){
    QActivity_ReleaseManage::display_result(['errorCode'=>202]);
}

QActivity_ReleaseManage::display_result(['errorCode'=>0]);
