<?php

/**
 * 图片审核通过接口，支持批量
 * eid使用英文逗号,分割
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$eid = $url_params['eid'] ? $url_params['eid'] : '';
if( $eid ){
    $eid_arr = explode(",", $eid);
}else{
    $eid_arr = [];
}

//验证用户登录
QImgPersonal::checkUserLoginNew(['callback'=>$callback]);

$params = [
    'eid' => $eid_arr,
    'username' => $login_user_name,
];

$audit_rs = QImgAuditOperate::auditPassBatch($params);
if( !$audit_rs || !$audit_rs['ret'] ){
    $rs = [
        "status" => 107,
        "message" => $audit_rs['msg'] ? $audit_rs['msg'] : "操作失败",
    ];
}else{
    $rs = [
        "status" => 0,
        "message" => "操作成功",
    ];
}

display_json_str_common($rs, $callback);