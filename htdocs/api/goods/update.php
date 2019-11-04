<?php

/**
 * 更新商品
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "GoodsValidator.php";

must_login();
session_write_close();//关闭session

$postParams = json_input();
$goodsValidator = GoodsValidator::update();
$updateData = [
    "eid" => filter_var($postParams["eid"], FILTER_VALIDATE_INT) ?: "",
    "title" => trim(filter_var($postParams["title"], FILTER_SANITIZE_STRING) ?: ""),
    "description" => trim(filter_var($postParams["description"], FILTER_SANITIZE_STRING) ?: ""),
    "img_url" => array_filter(array_map(function($item){
        if (filter_var($item, FILTER_VALIDATE_URL)) {
            $uri = parse_url($item)["path"];
            $imgKey = substr($uri, strpos($uri, "/", 1) + 1);
        }else{
            $imgKey = trim($item);
        }
        $imgKeyArr = explode(".", $imgKey);
        // 图片地址传的可能是压缩后的图片地址，这里处理一下
        return count($imgKeyArr) <= 2 ? $imgKey : current($imgKeyArr) . "." .end($imgKeyArr);
    }, $postParams["img_url"]), function ($item) {
        return ! empty($item);
    }),
    "exchange_begin_time" => filter_var($postParams["exchange_begin_time"], FILTER_SANITIZE_STRING) ?: null,
    "exchange_end_time" => filter_var($postParams["exchange_end_time"], FILTER_SANITIZE_STRING) ?: null,
    "exchange_description" => filter_var($postParams["exchange_description"], FILTER_SANITIZE_STRING) ?: null,
    "price" => $postParams["price"] ? number_format($postParams["price"], 2, '.', '') : null,
    "points" => filter_var($postParams["points"], FILTER_VALIDATE_INT) ?: "",
    "stock" => filter_var($postParams["stock"], FILTER_VALIDATE_INT) ?: "",
    "detail" => filter_var($postParams["detail"], FILTER_SANITIZE_STRING) ?: null,
    "detail_title" => trim(filter_var($postParams["detail_title"], FILTER_SANITIZE_STRING) ?: ""),
    "detail_img" => array_filter(array_map(function($item){
        if (filter_var($item, FILTER_VALIDATE_URL)) {
            $uri = parse_url($item)["path"];
            return substr($uri, strpos($uri, "/", 1) + 1);
        }
        return trim($item);
    }, $postParams["detail_img"]), function ($item) {
        return ! empty($item);
    }),
];
if (! $goodsValidator->pass($updateData)) {
    error_return($goodsValidator->getFirstError());
}
$goodsInstance = new QShop_Goods();
if (! $goodsInstance->find($updateData["eid"])) {
    error_return("商品不存在");
}

if (! $goodsInstance->update($updateData["eid"], $updateData, $login_user_name)) {
    error_return("商品更新失败");
}
success_return("商品更新成功");
