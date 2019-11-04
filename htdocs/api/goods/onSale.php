<?php

/**
 * 商品后台，上架商品
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "GoodsValidator.php";

must_login();
session_write_close();//关闭session

$postParams = json_input();
$goodsEid = filter_var($postParams["eid"], FILTER_VALIDATE_INT) ?: "";
$goodsValidator = GoodsValidator::info();
if (! $goodsValidator->pass(["eid" => $goodsEid])) {
    error_return($goodsValidator->getFirstError());
}
$goodsInstance = new QShop_Goods();
if (! $goodsInfo = $goodsInstance->find($goodsEid)) {
    error_return("商品不存在");
}

if (! $goodsInfo = $goodsInstance->onSale($goodsEid, $login_user_name)) {
    error_return("商品上架失败");
}

success_return("商品上架成功");
