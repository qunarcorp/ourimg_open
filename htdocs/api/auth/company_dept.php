<?php

/**
 * 公司部门列表
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "AuthValidator.php";

must_login();

$validate = AuthValidator::companyDept();
if (! $validate->pass((array) $_GET)) {
    error_return($validate->getFirstError());
}

$deptId = filter_input(INPUT_GET, 'dept_id', FILTER_VALIDATE_INT) ?: 0;
$role = filter_input(INPUT_GET, 'role', FILTER_SANITIZE_STRING) ?: "";

$employeeAndManagerNum = QEmployee::employeeAndManagerNum();
success_return("查询成功", [
    "employee_num" => intval($employeeAndManagerNum["employee_aggregate"]),
    "manager_num" => intval($role == 'admin'
        ? $employeeAndManagerNum["admin_aggregate"]
        : ($role == 'design'
            ? $employeeAndManagerNum["design_aggregate"]
            : $employeeAndManagerNum["manager_aggregate"]
        )),
    "dept_list" => QEmployee::companyDept($deptId, $role)
]);