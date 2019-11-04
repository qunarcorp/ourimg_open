<?php

/**
 * 用户搜索记录框 suggest接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING);

$params = [
    'query' => $query,
];
$query_rs = QOrderInfo::exchangeOrderQuerySuggest($params);

display_json_str_common($query_rs);
