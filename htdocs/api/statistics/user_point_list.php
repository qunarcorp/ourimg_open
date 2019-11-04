<?php

/**
 * 统计：用户积分详情
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", (new QStatistics_Points())->userPointList(
    page_size(),
    current_page(),
    $_GET['tabType'],
    $_GET['sortField'],
    $_GET['sortOrder'],
    $_GET['query']
));
