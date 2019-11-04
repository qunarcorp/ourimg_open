<?php

/**
 * 默认驳回拒绝原因
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$allDefaultRejectReason = QImgRejectReason::getAllDefaultRejectReason();

success_return("查询成功", $allDefaultRejectReason);