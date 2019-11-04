<?php

/**
 * 添加购物车 eid
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$json = array("status"=>1,"message"=>"","data"=>"");

$eid= filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_STRING);


if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

//参数错误
if(empty($eid) || !is_numeric($eid)){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}

$img_info = QImg::getInfo(array("eid"=>$eid,"audit_state"=>2,"is_del"=>"f"));

if(empty($img_info)){
    $json['status'] = 101;
    $json['message'] = "图片不存在";
    display_json_str_common($json,$callback);
}

//验证用户权限
$params_authcheck = [
    'upload_username' => $img_info['username'],
    'download_username' => $login_user_name,
];
QDownloadAuth::returnAuthCheckNew($params_authcheck);

//判断是否存在于购物车中
$adds = QImgShopCart::getIsAddShop($login_user_name,array($img_info['id']));
if($adds[$img_info['id']]){
    $json['status'] = 102;
    $json['message'] = "已存在于购物车中";
    display_json_str_common($json,$callback);
}

if(!QImgShopCart::add($login_user_name,$img_info['id'])){
    $json['status'] = 107;
    $json['message'] = "保存数据失败";
    display_json_str_common($json,$callback);
}

$json['status'] = 0;
$json['message'] = "保存成功";
display_json_str_common($json,$callback);

