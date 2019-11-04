<?php

/**
 * 图片删除
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = $url_params['callback'] ? $url_params['callback'] : '';

$eid = DB::EscapeString(trim($url_params['eid'] ? $url_params['eid'] : '', ","));
//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

if (! $eid){
    display_json_str_common([
        "ret" => false,
        "msg" => "请选择删除的图片",
    ], $callback);
}

try{
    QImgOperate::del((array) $eid, $login_user_name);

    display_json_str_common([
        "ret" => true,
        "msg" => "操作成功",
    ], $callback);
}catch (\QImgApiException $e){
    display_json_str_common([
        "ret" => false,
        "msg" => $e->getMessage(),
    ], $callback);
}