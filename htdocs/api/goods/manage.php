<?php

/**
 * 后台商城管理
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

must_login();
session_write_close();//关闭session

$perPage = min(max(intval($_GET["perPage"] ?: 10), 1), 200);
$searchQuery = trim(filter_input(INPUT_GET, "searchQuery", FILTER_SANITIZE_STRING));
$goodsSaleStatus = filter_input(INPUT_GET, "saleStatus", FILTER_SANITIZE_STRING);
$orderBy = filter_input(INPUT_GET, "orderBy", FILTER_SANITIZE_STRING);

if ($searchQuery){
    QShop_SearchRecord::create($searchQuery, "product", $login_user_name);
}

$goodsList = (new QShop_GoodsList)->paginateForManage($perPage, $searchQuery, $goodsSaleStatus, $orderBy);

success_return("查询成功", $goodsList);