<?php

/**
 * detail页查询
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_NUMBER_INT);

$params = [
    "eid" => $eid,
    "username" => $login_user_name ? $login_user_name : "",
];

//更新浏览量
$browse_rs = QImgMyBrowse::updateBrowseCount($params);

//获取图片列表--其实只有一条数据
$img_info = QImgSearch::getOneImg($params);
if( !$img_info && is_array($img_info) ){
    $rs = [
        "ret" => false,
        "msg" => "查询失败",
        "data" => [],
    ];
    display_json_str_common($rs, $callback);
}

//获取图片需要返回的字段
$imgs[] = $img_info;
$img_deal = QImgSearch::dealImgInfos($imgs,['username' => $login_user_name]);
//判断未审核通过只能自己查看
if( $img_deal[0]['audit_state'] != 2 ){
    if( $login_user_name != $img_deal[0]['username'] ){
        $rs = [
            "ret" => false,
            "msg" => "图片不存在",
            "data" => [],
        ];
        display_json_str_common($rs, $callback);
    }
}

//获取用户信息
$params = [
    'username' => $img_deal[0]['username'],
];
//这块用户信息柱哥提供，不需要请求qt接口
$userinfo = QAuth::getByParams($params);

$img_deal[0]['realname'] = $userinfo['name'];
$img_deal[0]['img_url'] = $userinfo['img'];
$img_deal[0]['copyright_auth_date'] = $userinfo['auth_time'] ? date("Y-m-d", strtotime($userinfo['auth_time'])) : '';
$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $img_deal[0],
];
display_json_str_common($rs, $callback);