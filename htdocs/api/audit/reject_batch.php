<?php

/**
 * 批量驳回接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);

//验证用户登录
QImgPersonal::checkUserLoginNew();

$eid = (array) ($url_params['eid'] ? $url_params['eid'] : []);
$reject_reason = (array) ($url_params['reject_reason'] ? $url_params['reject_reason'] : []);//违规原因

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

$rejectValidate = QValidator_Reject::batchReject();
if (! $rejectValidate->pass($params)) {
    error_return($rejectValidate->getFirstError());
}

$success_eid_arr = [];
$fail_eid_arr = [];
$fail_info = [];
foreach( $params["eid"] as $eid ){
    $audit_rs = QImgAuditOperate::auditReject($eid, $login_user_name, array_merge($defaultRejectReason, [$customRejectReason]));
    if( !$audit_rs || !$audit_rs['ret'] ){
        $fail_eid_arr[] = $eid;
        $fail_info[] = "图片{$eid}：{$audit_rs['msg']}";
    }else{
        $success_eid_arr[] = $eid;
    }
}

if( !$fail_eid_arr ){
    $rs = [
        "status" => 0,
        "message" => "操作成功",
    ];
    display_json_str_common($rs);
}else{
    $fail_message = implode("；",$fail_info);
        
    $rs = [
        "status" => 107,
        "message" => "失败图片信息如下：".$fail_message,
    ];
    display_json_str_common($rs);
}
