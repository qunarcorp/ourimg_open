<?php

/**
 * 
 * 商品兑换接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$url_params = json_decode(file_get_contents("php://input"),true);
$rs = QOrder::exchangeOrder($url_params);

if($rs['status'] ===0){
    //兑换成功 发消息
    QNotify::pointExchangeTip($login_user_name);
}

display_json_str_common($rs);
