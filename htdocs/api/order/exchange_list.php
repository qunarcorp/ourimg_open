<?php

/**
 * 商品兑换列表
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);

$params = [
    'offset' => $offset,
    'limit' => $limit,
    'username' => $login_user_name,
];
$order_list = QOrderInfo::getExchangeOrders($params);


$order_arr = QOrderInfo::dealExchangeOrders($order_list);

$rs = [
    'status' => 0,
    'data' => [
        'order_list' => $order_arr,
        'order_count' => QOrderInfo::getExchangeOrderCount($params),
    ],
];

display_json_str_common($rs);
