<?php

/**
 * 商品兑换列表
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);
$state = filter_input(INPUT_GET, 'state', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);

$params = [
    'offset' => $offset,
    'limit' => $limit,
    'state' => $state,
    'query' => $query,
];

//限制query字数
if( $query && mb_strlen($query) > 20 ){
    $rs = [
        'status' => 1017,
        'message' => '搜索关键词不能超过20个字',
    ];
    display_json_str_common($rs);
}

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
