<?php

/**
 * 删除购物车 取消加入购物车，根据图片的加密eid
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$json = array("status"=>1,"message"=>"","data"=>"");

//img_eid
$eid= filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_STRING);


if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

if(empty($eid) || !is_numeric($eid)){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}

/**
 * 获取图片信息
 */
$img_info = QImg::getInfo(array("eid"=>$eid,"is_del"=>"f"));

if(!$img_info){
    //不存在，或者图片的用户名不是当前等录的用户
    $json['status'] = 101;
    $json['message'] = "图片不存在";
    display_json_str_common($json,$callback);
}
/**
 * 获取购物车信息
 */
$cart_info = QImgShopCart::getByImgId($login_user_name,$img_info['id']);
if(empty($cart_info)){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}


/**
 * 删除使用购物车的eid
 */
if(!QImgShopCart::del($login_user_name,array($cart_info['eid']))){
    $json['status'] = 107;
    $json['message'] = "删除数据失败";
    display_json_str_common($json,$callback);
}

$json['status'] = 0;
$json['message'] = "删除成功";
display_json_str_common($json,$callback);
