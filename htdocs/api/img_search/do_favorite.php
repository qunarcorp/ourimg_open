<?php

/**
 * 收藏接口|取消收藏
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = $url_params['callback'] ? $url_params['callback'] : '';
$state = $url_params['state'] ? $url_params['state'] : '';
$favorite_type = $url_params['favorite_type'] ? $url_params['favorite_type'] : 'img';

$favorite_type = html_encode($favorite_type);

$eid = $url_params['eid'] ? $url_params['eid'] : '';
//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

$params = [
    'eid' => $eid,
    'username' => $login_user_name,
    'favorite_type' => $favorite_type,
];
if( $state == "cancel" ){//取消操作
    $download_rs = QImgMyFavorite::cancelFavorite($params);
}else{//正常操作
    $download_rs = QImgMyFavorite::userFavorite($params);
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