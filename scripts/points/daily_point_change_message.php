<?php

/**
 * 每日发送前一日积分变动消息通知
 *
 *  01 9 * * *
 */

require_once __DIR__."/../../htdocs/app_api.php";

//保证当前只有一个任务在执行
crontab_run_one("crontab","img_points_message_mark");

$log_prefer = 'crontab';
$log_action = 'img_points_message';

//记录日志，开始执行
QLog::info($log_prefer,$log_action,"开始计算用户昨日积分变动");

$begin_time = date("Y-m-d 00:00:00",strtotime("-1 day"));
$end_time = date("Y-m-d 00:00:00");

$points_sql = " SELECT username, SUM(change_points) AS change_points FROM "
        . QImgSearch::$pointsTraceTableName
        ." WHERE create_time >= '{$begin_time}' AND create_time < '{$end_time}' GROUP BY username ";
$points_rs = DB::GetQueryResult($points_sql, false);

foreach( $points_rs as $k => $v ){
    
    if( $v['change_points'] > 0 ){
        $type_str = "增加";
    }else{
        $type_str = "减少";
    }
    
    $change_points = abs($v['change_points']);

    // 积分变化大于0，发送消息
    if ($change_points) {
        //发送消息
        $message = "您昨日积分共".$type_str.$change_points;
        QImgMessage::addPointsMessage ($v['username'],$message,'2');
    }
}

QLog::info($log_prefer,$log_action,"昨日积分变动消息发送完成");
