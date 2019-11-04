<?php

/**
 * 排行榜：积分
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$pageSize = $_GET["pageSize"];
success_return("查询成功", QRank::point($_GET["pageSize"] ?: 10));
