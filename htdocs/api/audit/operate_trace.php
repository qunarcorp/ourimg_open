<?php

/**
 * 操作流程--根据产品eid查询一个，如果数量太多会影响效率，所以只能一个
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_STRING);

//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);

//获取分状态的数量
$params = [
    'eid' => $eid,
];
$db_rs = QImgOperateTrace::getOperateTraces($params);
if( !$db_rs || !$db_rs['ret'] || !$db_rs['data'] ){
    $rs = [
        "status" => 107,
        "message" => $db_rs['msg'] ? $db_rs['msg'] : "查询失败",
    ];
}else{
    $deal_rs = QImgOperateTrace::dealOperateTraces($db_rs['data']);
    $rs = [
        "status" => 0,
        "data" => $deal_rs,
        "message" => '查询成功',
    ];
}

display_json_str_common($rs, $callback);
