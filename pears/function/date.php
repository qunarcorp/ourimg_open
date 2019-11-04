<?php

/**
 * 获取当前时间与传入的时候差的字符串
 * @param $date  2018-01-01 10:00:01
 * @return string 1年前
 */
function date_diff_str($date){
    $diff = date_diff(new DateTime(date("Y-m-d H:i:s",strtotime($date))),new DateTime(date("Y-m-d H:i:s")));

    if($diff->y){
        $unit = "1年";
    }elseif($diff->m){
        $unit = "{$diff->m}月";
    }elseif($diff->d){
        $unit = "{$diff->d}天";
    }elseif($diff->h){
        $unit = "{$diff->h}小时";
    }elseif($diff->i){
        $unit = "{$diff->i}分钟";
    }elseif($diff->s){
        $unit = "{$diff->s}秒";
    }
    return  $unit."前上传";
}

/**
 * 获取当前的微秒
 * @return mixed
 */
function get_now_microtime(){
    return microtime(true);
}