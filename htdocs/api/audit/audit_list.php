<?php

/**
 * 素材审核列表：待审核、已通过、未通过、已下架，按状态区分
 * 审核状态：1-待审核；2-审核通过；3-审核驳回；4-已删除下架
 * 关键字：英文逗号分割
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$audit_state = filter_input(INPUT_GET, 'audit_state', FILTER_SANITIZE_NUMBER_INT);
$keyword = array_filter(array_unique(explode(",", $keyword)));

//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);

//获取分状态的数量
$params = [
    'keyword' => $keyword,
    'audit_state' => $audit_state,
    'offset' => $offset,
    'limit' => $limit ? $limit : 10,
    'is_del' => $audit_state == 4 ? 'all' : 'f',
];
$db_rs = QImgAudit::getAuditList($params);
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
        "count" => QImgSearch::getImgCount($params),//获取数量
        "data" => $deal_rs,
        "message" => '查询成功',
    ];
}

display_json_str_common($rs, $callback);

