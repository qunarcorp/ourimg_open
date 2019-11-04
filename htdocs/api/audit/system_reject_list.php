<?php

/**
 * 系统驳回列表接口
 * 关键字：英文逗号分割
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$keyword = array_filter(array_unique(explode(",", $keyword)));

//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);

//获取分状态的数量
$audit_state = 3;
$params = [
    'keyword' => $keyword,
    'audit_state' => $audit_state,
    'is_system_check' => 1,//只查询系统审核驳回的图片
    'offset' => $offset,
    'limit' => $limit ? $limit : 10,
];
$db_rs = QImgAudit::getSystemRejectList($params);
if( !$db_rs || !$db_rs['ret'] || !$db_rs['data'] ){
    $rs = [
        "status" => 0,
        "count" => 0,
        "message" => $db_rs['msg'] ? $db_rs['msg'] : '没有查询到数据',
        "data" => [],
    ];
}else{
    //处理结果
    $deal_rs = QImgAudit::getAuditArrDetails($db_rs['data']);

    $rs = [
        "status" => 0,
        "count" => QImgAudit::getSystemRejectCounts(),//获取数量
        "data" => $deal_rs,
        "message" => '查询成功',
    ];
}

display_json_str_common($rs, $callback);

