<?php

/**
 * 排行榜：点赞
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", QRank::praise($_GET["pageSize"] ?: 10));
