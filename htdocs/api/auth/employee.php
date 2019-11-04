<?php

/**
 * 查询用户
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "AuthValidator.php";

must_login();

$validate = AuthValidator::employeeSearch();
if (! $validate->pass((array) $_GET)) {
    error_return($validate->getFirstError());
}

$currentPage = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$pageSize = filter_input(INPUT_GET, 'page_size', FILTER_VALIDATE_INT) ?: 20;
$query = trim(DB::EscapeString(filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING) ?: ""));
$deptId = intval(filter_input(INPUT_GET, 'dept_id', FILTER_VALIDATE_INT) ?: 0);

success_return('查询成功', $login_auth->searchUser(["deptid"=>$deptId,'username'=>$query,'currentPage'=>$currentPage,'pageSize'=>$pageSize]));

