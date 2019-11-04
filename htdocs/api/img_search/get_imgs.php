<?php

/**
 * 图片查询接口
 * (1)个人主页-我的上传
 * (2)首页搜索
 * (3)list页搜索
 * (4)为你推荐
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

//参数获取
$ext = filter_input(INPUT_GET, 'ext', FILTER_SANITIZE_STRING);
$page_source = filter_input(INPUT_GET, 'page_source', FILTER_SANITIZE_STRING);
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$audit_state = filter_input(INPUT_GET, 'audit_state', FILTER_SANITIZE_NUMBER_INT);
$size_type = filter_input(INPUT_GET, 'size_type', FILTER_SANITIZE_STRING);
$big_type = filter_input(INPUT_GET, 'big_type', FILTER_SANITIZE_STRING);
$small_type = filter_input(INPUT_GET, 'small_type', FILTER_SANITIZE_STRING);
$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_NUMBER_INT);
$location = json_decode($_GET['location'], true);

$keyword = html_encode($keyword);
$size_type = html_encode($size_type);
$small_type = html_encode($small_type);
$page_source = html_encode($page_source);


$keyword = array_filter(array_unique(explode(",", $keyword)));
$small_type = array_filter(array_unique(explode(",", $small_type)));
$size_type = array_filter(array_unique(explode(",", $size_type)));
$ext = array_filter(array_unique(explode(",", $ext)));

//验证用户登录--my我的页面、others他人个人主页、list搜索
//如果是个人主页，自己获取当前登录用户
if( $page_source == 'my' ){
    //验证用户登录
    QImgPersonal::checkUserLogin(['callback'=>$callback]);
    $username = $login_user_name;
}elseif( $page_source == 'others' ){
    $username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_STRING);
}else{
    //记录keyword搜索信息--所有的都记录
    QImgOperate::updateSearchs($_GET);
}

$my_username = $login_user_name;

//我的或他人的个人主页，需要获取用户信息
if(in_array($page_source, ['my','others'])&&$username){
    $userinfo_rs = QImgPersonal::getUserInfo(['username'=> $username]);
}

//获取图片数组
$params = [
    "location" => $location,
    "eid" => $eid,
    "small_type" => $small_type,
    "size_type" => $size_type,
    "username" => $username?$username:'',
    "sort_by" => $sort_by,
    "offset" => $offset,
    "limit" => $limit,
    "audit_state" => $audit_state,
    "keyword" => $keyword,
    "ext" => $ext,
    "page_source" => $page_source,
    "big_type" => $big_type ?: 1,
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
$deal_imgs = QImgSearch::dealImgInfos($imgs, ['username' => $my_username]);

$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $deal_imgs,
    "count" => QImgSearch::getImgCount($params),
    "userinfo" => $userinfo_rs,
];
display_json_str_common($rs, $callback);