<?php

/**
 * 用户操作授权
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

//没传验证登录用户
QImgPersonal::checkUserLogin(['callback'=>$callback]);


$params = [
    "username" => $login_user_name,
];
//获取统计计数
$check_rs = QImgMyUpload::userAuth($params);
if( $check_rs ){
    $rs = [
        "status" => 0,
        "msg" => "用户授权成功",
    ];
}else{
    $rs = [
        "status" => 111,
        "msg" => "用户授权失败",
    ];
}

display_json_str_common($rs, $callback);