<?php

/**
 * 图片查询接口
 * 他人的主页
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

//参数获取
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$big_type = filter_input(INPUT_GET, 'big_type', FILTER_SANITIZE_STRING);

$keyword = array_filter(array_unique(explode(",", $keyword)));
$username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_STRING);
$uploadSource = filter_input(INPUT_GET, 'dept', FILTER_SANITIZE_STRING);

//我的或他人的个人主页，需要获取用户信息
$userinfo_rs = ! empty($uploadSource)
    ?
    [
        "user_img" => "https://qt.qunar.com/file/v2/download/perm/ff1a003aa731b0d4e2dd3d39687c8a54.png?&w=120&h=120",
        "points_info" => [
            "total_points" => "0",
            "current_points" => "0",
            "last_date_points" => "+0"
        ],
        "role" => ["normal"],
        "username" => "",
        "name" => $uploadSource,
        "auth_state" => "0",
        "auth_date" => "",
        "dept" => ["去哪儿网"],
    ]
    : QImgPersonal::getUserInfo(['username'=> $username]);
if( (!$username && !$uploadSource) || !$userinfo_rs ){
    $rs = [
        "ret" => false,
        "msg" => "用户信息有误",
    ];
    display_json_str_common($rs, $callback);
}

//获取图片数组
$params = [
    "username" => empty($uploadSource) ? ($username ? $username : '') : '',
    "upload_source" => $uploadSource ? $uploadSource : '',
    "sort_by" => $sort_by,
    "offset" => $offset,
    "limit" => $limit,
    "keyword" => $keyword,
    "big_type" => $big_type,
];

$imgs = QImgSearch::getImgs($params);
if( !$imgs || !is_array($imgs) ){
    $rs = [
        "ret" => true,
        "msg" => "查询失败",
        "data" => [],
        "count" => 0,
        "userinfo" => $userinfo_rs,
    ];
    display_json_str_common($rs, $callback);
}

//处理图片数组返回参数
$deal_imgs = QImgSearch::dealImgInfos($imgs, ['username' => $login_user_name]);

$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $deal_imgs,
    "count" => QImgSearch::getImgCount($params),
    "userinfo" => $userinfo_rs,
];
display_json_str_common($rs, $callback);