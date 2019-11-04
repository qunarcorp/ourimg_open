<?php

/**
 * 商品信息
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "GoodsValidator.php";

must_login();
session_write_close();//关闭session

$goodsEid = filter_input(INPUT_GET, "eid", FILTER_VALIDATE_INT) ?: "";
$action = filter_input(INPUT_GET, "action", FILTER_SANITIZE_STRING) ?: "";
$goodsValidator = GoodsValidator::info();
if (! $goodsValidator->pass(["eid" => $goodsEid])) {
    error_return($goodsValidator->getFirstError());
}
$goodsInstance = new QShop_Goods();
if (! $goodsInfo = $goodsInstance->find($goodsEid)) {
    error_return("商品不存在");
}

if ($action != "edit") {
    $goodsInstance->browse($goodsEid);
}
success_return("查询成功", $goodsInfo);
