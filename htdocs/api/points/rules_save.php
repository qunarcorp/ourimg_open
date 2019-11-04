<?php

/**
 * 保存积分规则
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$url_params = json_decode(file_get_contents("php://input"),true);



$save_rs = QPointRule::savePointRules($url_params);

display_json_str_common($save_rs);
