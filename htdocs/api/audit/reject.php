<?php

/**
 * 图片审核驳回接口
 * 补充说明：每次驳回只能一个eid，不支持批量
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

if (empty($url_params)) {
    error_return("参数错误");
}
$eid = $url_params['eid'] ? $url_params['eid'] : '';
$reject_reason = $url_params['reject_reason'] ? $url_params['reject_reason'] : [];//违规原因

$reject_reason = array_filter((array) $reject_reason,  function ($reason){
    return ! empty($reason);
});

list($defaultRejectReason, $customRejectReason) = array_values(QImgRejectReason::differentiateRejectReasonType((array) $reject_reason));
$customRejectReason = (string) (! empty($customRejectReason) ? current($customRejectReason) : "");
$params = [
    "eid" => $eid,
    "reject_reason" => $reject_reason,
    "default_reject_reason" => $defaultRejectReason,
    "custom_reject_reason" => $customRejectReason,
];

$rejectValidate = QValidator_Reject::reject();
if (! $rejectValidate->pass($params)) {
    error_return($rejectValidate->getFirstError());
}

//验证用户登录
QImgPersonal::checkUserLoginNew(['callback' => $callback]);


$audit_rs = QImgAuditOperate::auditReject($eid, $login_user_name, array_filter(array_merge($defaultRejectReason, [$customRejectReason]), function ($item){
    return ! empty($item) && $item != '""' && $item != '\'\'';
}));

if(! $audit_rs || ! $audit_rs['ret'] ){
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