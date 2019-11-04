<?php

/**
 * 待提交的删除
 */
require_once __DIR__."/../app_api.php";
session_write_close();//关闭session
$eids= DB::EscapeString(trim(filter_input(INPUT_GET, 'eids', FILTER_SANITIZE_STRING), ","));

if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

if(! $eids){
    display_json_str_common([
        "ret" => false,
        "msg" => "请选择删除的图片",
    ], $callback);
}

try{
    QImgOperate::del(explode(",", $eids), $login_user_name);
    success_return("操作成功");
}catch (\QImgApiException $e){
    error_return($e->getMessage(), ["message_ext" => $eids], 102);
}

