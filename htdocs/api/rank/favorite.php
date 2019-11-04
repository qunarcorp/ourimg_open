<?php

/**
 * 排行榜：收藏
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", QRank::favorite($_GET["pageSize"] ?: 10));
