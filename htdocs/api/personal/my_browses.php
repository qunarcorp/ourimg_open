<?php

/**
 * 个人主页-我的浏览足迹
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$time_id = filter_input(INPUT_GET, 'time_id', FILTER_SANITIZE_NUMBER_INT);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_NUMBER_INT);
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
$keyword = array_filter(array_unique(explode(",", $keyword)));

//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);
$username = $login_user_name;
//获取用户信息
$userinfo_rs = QImgPersonal::getUserInfo(['username'=> $username]);

$params = [
    'username' => $username,
    "offset" => $offset,
    "limit" => $limit,
    "sort_by" => $sort_by,
    "keyword" => $keyword,
    "time_id" => $time_id,
    "deleted_after" => 1,//已删除图片置后
];
$my_favorites = QImgMyBrowse::getMyBrowses($params);
if( !$my_favorites || !is_array($my_favorites) ){
    $rs = [
        "ret" => true,
        "msg" => "查询失败",
        "data" => [],
        "count" => 0,
        "userinfo" => $userinfo_rs,
    ];
    display_json_str_common($rs, $callback);
}
$deal_imgs = QImgSearch::dealImgInfos($my_favorites,['username'=> $username]);
    
$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $deal_imgs,
    "count" => QImgMyBrowse::getMyBrowseCount($params),
    "userinfo" => $userinfo_rs,
];
display_json_str_common($rs, $callback);