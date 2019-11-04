<?php

/**
 * 删除管理员
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "AuthValidator.php";

must_login();

$validate = AuthValidator::removePower();
if (! $validate->pass((array) $_POST)) {
    error_return($validate->getFirstError());
}

$role = $_POST['role'];
$username = DB::EscapeString($_POST['username']);

// 超级管理员添加管理员，管理员添加运营人员,运营人员无权限
if ($role == 'admin' && ! QAuth::isSuperAdmin()){
    no_permission();
}

$username = array_map(function ($item){
    return trim($item);
}, explode(',', $username));

if (count($username) > 100){
    error_return("单次添加不得超过100人");
}

if (QAuth::removePower($username, $role)) {
    success_return("操作成功");
}else{
    error_return("操作失败");
}
