<?php


/**
 * 订单发货
 */

require(dirname(dirname(dirname(dirname(__FILE__)))) . '/app_api.php');

must_login();
session_write_close();//关闭session

$postParams = json_input();
$orderId = filter_var($postParams["orderId"], FILTER_SANITIZE_STRING) ?: "";
if (empty($orderId)) {
    error_return("订单id不能为空");
}

if (! QShop_Orders::exist($orderId)) {
    error_return("订单不存在");
}

if(! (new QShop_Orders())->ship($orderId, $login_user_name)) {
    error_return("不能重复发货");
}

success_return("发货成功");