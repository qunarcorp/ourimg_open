<?php

/**
 * 登录
 */

require(dirname(__FILE__) . '/app_api.php');

$action = filter_input(INPUT_GET,"action");


$login_auth->login();