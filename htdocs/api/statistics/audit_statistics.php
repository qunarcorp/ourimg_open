<?php

/**
 * 审核数据统计
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

success_return("查询成功", (new QStatistics_ImgAudit())->auditStatistics());
