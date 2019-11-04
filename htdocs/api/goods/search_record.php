<?php


/**
 * 搜索记录
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

must_login();
session_write_close();//关闭session

success_return("查询成功", QShop_SearchRecord::latest("product", $login_user_name, 20));