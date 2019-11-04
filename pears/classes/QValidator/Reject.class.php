<?php

/**
 * 图片审核相关校验
 * Class QValidator_Reject
 */

class QValidator_Reject
{
    /**
     * 图片审核驳回接口
     * @return QValidator
     */
    public static function reject()
    {
        return QValidator::make([
            "eid" => "require",
            "reject_reason" => "require|array",
            "default_reject_reason" => "array",
            "custom_reject_reason" => "length:1,50",
        ], [
            "eid.require" => "eid必填",
            "reject_reason.require" => "驳回原因不可为空",
            "reject_reason.array" => "驳回原因格式错误",
            "default_reject_reason.array" => "默认驳回原因错误",
            "custom_reject_reason.length" => "自定义驳回原因长度在1~50之间",
        ]);
    }

    /**
     * 图片批量审核驳回接口
     * @return QValidator
     */
    public static function batchReject()
    {
        return QValidator::make([
            "eid" => "require|array",
            "reject_reason" => "require|array",
            "default_reject_reason" => "array",
            "custom_reject_reason" => "length:1,50",
        ], [
            "eid.require" => "eid必填",
            "eid.array" => "eid格式错误",
            "reject_reason.require" => "驳回原因不可为空",
            "reject_reason.array" => "驳回原因格式错误",
            "default_reject_reason.array" => "默认驳回原因错误",
            "custom_reject_reason.length" => "自定义驳回原因长度在1~50之间",
        ]);
    }
}