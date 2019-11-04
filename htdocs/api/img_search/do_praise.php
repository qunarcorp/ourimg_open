<?php

/**
 * 点赞|取消点赞
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = $url_params['callback'] ? $url_params['callback'] : '';
$state = $url_params['state'] ? $url_params['state'] : '';

$eid = $url_params['eid'] ? $url_params['eid'] : '';

$eid = html_encode($eid);
//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

$params = [
    'eid' => $eid,
    'username' => $login_user_name,
];
if( $state == "cancel" ){//取消操作
    $download_rs = QImgOperate::cancelPraise($params);
}else{//正常操作
    $download_rs = QImgOperate::userPraise($params);
}

if( !$download_rs || !$download_rs['ret'] ){
    $rs = [
        "ret" => false,
        "msg" => $download_rs['msg'],
    ];
}else{
    $rs = [
        "ret" => true,
        "msg" => "操作成功",
    ];
}

display_json_str_common($rs, $callback);