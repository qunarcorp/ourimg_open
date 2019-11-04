<?php

/**
 * 获取登录方式
 */
require(dirname(__FILE__) . '/app_api.php');
$action = filter_input(INPUT_GET,"action");
if("get_login_way" ==$action){
    $login_auth->getLoginDriver();
}
