<?php

/**
 * 统计：地区图片数量
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", (new QStatistics_Location())->imgNum(
    (string) $_GET["level"] ?: 'country',
    (int) $_GET["pid"] ?: 0,
    (int) $_GET["page"] ?: 1,
    (int) $_GET["pageSize"] ?: 10
));
