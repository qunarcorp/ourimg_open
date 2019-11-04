<?php

/**
 * 退出登录
 */
require(dirname(__FILE__) . '/app_api.php');
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

$login_auth->loginOut();
