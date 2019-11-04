<?php

/**
 * 新建商品
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "GoodsValidator.php";

must_login();
session_write_close();//关闭session

$postParams = json_input();
$goodsValidator = GoodsValidator::store();
$storeData = [
    "title" => trim(filter_var($postParams["title"], FILTER_SANITIZE_STRING) ?: ""),
    "description" => trim(filter_var($postParams["description"], FILTER_SANITIZE_STRING) ?: ""),
    "img_url" => array_filter(array_map(function($item){
        if (filter_var($item, FILTER_VALIDATE_URL)) {
            $uri = parse_url($item)["path"];
            return substr($uri, strpos($uri, "/", 1) + 1);
        }
        return trim($item);
    }, $postParams["img_url"]), function ($item) {
        return ! empty($item);
    }),
    "exchange_begin_time" => filter_var($postParams["exchange_begin_time"], FILTER_SANITIZE_STRING) ?: null,
    "exchange_end_time" => filter_var($postParams["exchange_end_time"], FILTER_SANITIZE_STRING) ?: null,
    "exchange_description" => trim(filter_var($postParams["exchange_description"], FILTER_SANITIZE_STRING) ?: ""),
    "price" => $postParams["price"] ? number_format($postParams["price"], 2, '.', '') : null,
    "points" => filter_var($postParams["points"], FILTER_VALIDATE_INT) ?: "",
    "stock" => filter_var($postParams["stock"], FILTER_VALIDATE_INT) ?: "",
    "detail_title" => trim(filter_var($postParams["detail_title"], FILTER_SANITIZE_STRING) ?: ""),
    "detail" => trim(filter_var($postParams["detail"], FILTER_SANITIZE_STRING) ?: ""),
    "detail_img" => array_filter(array_map(function($item){
        if (filter_var($item, FILTER_VALIDATE_URL)) {
            $uri = parse_url($item)["path"];
            return substr($uri, strpos($uri, "/", 1) + 1);
        }
        return trim($item);
    }, $postParams["detail_img"]), function ($item) {
        return ! empty($item);
    }),
    "publish_status" => !! filter_var($postParams["publish_status"], FILTER_VALIDATE_BOOLEAN),
];
if (! $goodsValidator->pass($storeData)) {
    error_return($goodsValidator->getFirstError());
}

if (! (new QShop_Goods())->create($storeData, $login_user_name)) {
    error_return("创建商品失败");
}
success_return("创建商品成功");
