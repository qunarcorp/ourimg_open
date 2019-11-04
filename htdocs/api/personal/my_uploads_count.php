<?php

/**
 * 我的上传各个分类的数量接口
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
$count_rs = QImgSearch::getMyUploadTabsCount($params);

$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $count_rs,
];
display_json_str_common($rs, $callback);