<?php
/**
 * 固本配置调用
 */

@session_start();
require(dirname(dirname(__FILE__)) . '/pears/application.php');

//读取配置文件
$INI = configure_load();


//先写死当前登录的域名。系统内部
$system_domain = $INI['system']['system_domain'];

//编码设置
mb_internal_encoding('UTF-8');

$login_auth = new Login();
$login_user_name = $login_auth->getLoginUserName();
$login_real_name = $login_auth->getLoginName();
$login_info = $login_auth->getLoginInfo();

require(DIR_ROOT . '/conf/dictionary/img.php');

if (! QAuth::hasPermission()) {
    must_login();
    no_permission();
}

/**
 * content_type 为 application/json  将 php://input 赋值到 $_POST
 */
if (strtolower($_SERVER['CONTENT_TYPE']) == 'application/json') {
    $_POST = json_decode(file_get_contents("php://input"),true);
}