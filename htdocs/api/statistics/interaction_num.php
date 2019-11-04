<?php

/**
 * 统计:用户交互统计 总数
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", (new QStatistics_Interaction())->statistics());
