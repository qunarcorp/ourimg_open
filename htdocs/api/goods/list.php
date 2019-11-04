<?php

/**
 * 积分商城首页列表
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

must_login();
session_write_close();//关闭session

$perPage = min(max(intval($_GET["perPage"] ?: 10), 1), 200);
$orderBy = filter_input(INPUT_GET, "orderBy", FILTER_SANITIZE_STRING);
$filterCondition = filter_input(INPUT_GET, "filterCondition", FILTER_SANITIZE_STRING);

$goodsList = (new QShop_GoodsList)->paginate($perPage, $orderBy, $filterCondition, $login_user_name);

success_return("查询成功", $goodsList);