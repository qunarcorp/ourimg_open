<?php

/**
 * 各页面购物车总数badge
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$json = array("status"=>1,"message"=>"","data"=>"");

if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}
$params = array("username"=>$login_user_name,"domain_id"=>$system_domain);
$result = QImgShopCart::getCartCount($params);

$json['status'] = 0;
$json['data'] = intval($result['count']);

display_json_str_common($json,$callback);

