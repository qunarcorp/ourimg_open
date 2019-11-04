<?php

/**
 * 个人素材数据的体现，收藏总量，浏览总量，下载总量，点赞总量
 * 请求方式：get
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
//如果是其他人的个人主页-必传
$username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_STRING);
$uploadSource = filter_input(INPUT_GET, 'dept', FILTER_SANITIZE_STRING);

//验证用户信息
if( !$username ){
    //没传验证登录用户
    QImgPersonal::checkUserLogin(['callback'=>$callback]);
    //验证通过获取用户名
    $username = $login_user_name;
}

//获取统计计数
$count_rs = empty($uploadSource) ? QImgPersonal::getMyImgSum([
    "username" => $username ? $username : '',
]) : QImgPersonal::getDeptImgSum([
    "upload_source" => $uploadSource ? $uploadSource : '',
]);

$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $count_rs,
];
display_json_str_common($rs, $callback);