<?php

/**
 * 管理员列表
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "AuthValidator.php";

must_login();

$validate = AuthValidator::managerSearch();
if (! $validate->pass((array) $_GET)) {
    error_return($validate->getFirstError());
}

$role = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_STRING);
$currentPage = filter_input(INPUT_GET, 'current', FILTER_VALIDATE_INT) ?: 1;
$pageSize = filter_input(INPUT_GET, 'pageSize', FILTER_VALIDATE_INT) ?: 20;
$query = DB::EscapeString(filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING) ?: "");

$whereQuery = " domain_id = {$system_domain} AND role @> '[\"{$role}\"]' ";
$query and $whereQuery .= " AND (username ~ '{$query}' OR name ~ '{$query}') ";
$response = QAuth::getList(
    $whereQuery,
    $pageSize,
    $currentPage
);

$response["list"] = array_map(function($item) use ($role) {
    return [
        "username" => $item["username"],
        "node_str" => $item["dept"],
        "role" => $role,
        "realname" => $item["name"],
        "avatar" => $item["img"],
    ];
}, $response["list"]);

success_return('查询成功', $response);