<?php

/**
 * 判断用户是否登录
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
if(!isset($login_user_name) || empty($login_user_name)){
    $rs = [
        "status" => 0,
        "is_login" => false,
        "message" => "用户未登录",
        "data" => [],
        "count" => 0,
    ];
    display_json_str_common($rs, $callback);
}

$userinfo_rs = QImgPersonal::getUserInfo(['username'=> $login_user_name]);

$rs = [
    "status" => 0,
    "is_login" => true,
    "message" => "查询成功",
    "userinfo" => $userinfo_rs,
];
display_json_str_common($rs, $callback);