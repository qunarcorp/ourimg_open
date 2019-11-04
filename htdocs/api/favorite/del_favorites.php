<?php

/**
 * 清空用户收藏数据--已下架
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = $url_params['callback'] ? $url_params['callback'] : '';
$favorite_type = $url_params['favorite_type'] ? $url_params['favorite_type'] : 'img';
$favorite_type = html_encode($favorite_type);
//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

$params = [
    'username' => $login_user_name,
    'favorite_type' => $favorite_type,
];

$rs = QImgMyFavorite::delFavotites($params);

display_json_str_common($rs, $callback);