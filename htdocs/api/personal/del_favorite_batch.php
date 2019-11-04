<?php

/**
 * 批量取消收藏
 * eid多个使用英文逗号,分割
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = $url_params['callback'] ? $url_params['callback'] : '';
$favorite_type = $url_params['favorite_type'] ? $url_params['favorite_type'] : 'img';

$eid = $url_params['eid'] ? $url_params['eid'] : '';
$eid_arr = explode(",", $eid);
//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

$params = [
    'username' => $login_user_name,
    'favorite_type' => $favorite_type,
    'eid' => $eid_arr,
];

$download_rs = QImgMyFavorite::cancelFavoriteAll($params);

if( !$download_rs || !$download_rs['ret'] ){
    $rs = [
        "ret" => false,
        "msg" => $download_rs['msg'],
    ];
    display_json_str_common($rs, $callback);
}
$rs = [
    "ret" => true,
    "msg" => "操作成功",
];
display_json_str_common($rs, $callback);