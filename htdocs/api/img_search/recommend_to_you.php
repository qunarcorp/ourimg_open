<?php

/**
 * 图片查询接口
 * (4)为你推荐
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
//参数获取
//$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
//$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$limit = $limit > 0 ? $limit : 20;
$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_NUMBER_INT);

//$keyword = array_filter(array_unique(explode(" ", $keyword)));

if( !$eid ){
    $rs = [
        "ret" => true,
        "msg" => "参数有误",
        "data" => [],
        "count" => 0,
    ];
    display_json_str_common($rs, $callback);
}

if(!empty($login_user_name)){
    $my_username = $login_user_name;
}

//获取图片数组
$params = [
    "eid" => $eid,
];

$imgs = QImgSearch::getRecommends($params);
if( !$imgs['ret'] || !is_array($imgs['data']) ){
    $rs = [
        "ret" => true,
        "msg" => $imgs['msg'] ? $imgs['msg'] : "查询失败",
        "data" => [],
        "count" => 0,
    ];
    display_json_str_common($rs, $callback);
}

//处理图片数组返回参数
$deal_imgs = QImgSearch::dealImgInfos($imgs['data'], ['username' => $my_username]);

$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => array_values($deal_imgs),
    "count" => QImgSearch::getImgCount($params) - 1,
];
display_json_str_common($rs);