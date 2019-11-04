<?php

/**
 * 用户数据统计 总数
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", (new QStatistics_UserStatistics())->userStatistics());