<?php

/**
 * 素材按状态分类-返回数量接口
 * 待审核、已通过、未通过、已下架
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);

//获取分状态的数量
$rs = QImgAudit::getAuditCounts();

$system_reject_count = QImgAudit::getSystemRejectCounts();
$rs['data']['system_reject_count'] = $system_reject_count;

display_json_str_common($rs, $callback);
