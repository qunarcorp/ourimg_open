<?php

/**
 *
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require_once "AuthValidator.php";

must_login();

success_return("查询成功", array_column(
    QEmployee::companyDept()['dept'],
    'dept_name'
));