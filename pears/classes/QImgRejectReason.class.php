<?php

/**
 * 图片驳回理由
 * Class QImgRejectReason
 */

class QImgRejectReason
{
    const REJECT_REASON = [
        [
            "category_name" => "图片信息问题",
            "tags" => [
                "标题不完善", "图片分类有误", "拍摄地点不明确", "拍摄地点有误", "关键词不够丰富", "信息含敏感内容", "图片关键词含符号"
            ],
        ],
        [
            "category_name" => "图片质量问题",
            "tags" => [
                "图片含敏感元素", "曝光不足", "曝光过度", "背景过于杂乱", "噪点太多", "图片分辨率过低", "主体不够突出", "图片压缩过度",
                "地平线不齐", "没有明确的主题", "图片有水印", "图片上有文字", "图片不是原图", "构图比例问题", "画质不清晰"
            ],
        ],
        [
            "category_name" => "图片版权相关",
            "tags" => [
                "人物肖像无授权书", "疑似网络图", "明星肖像不可用", "品牌商标不可用"
            ],
        ],
    ];

    /**
     * get all default reject reason
     * @return array
     */
    public static function getAllDefaultRejectReason()
    {
        return self::REJECT_REASON;
    }

    /**
     * differentiate reject reason type
     * @param $rejectReason
     * @return array
     */
    public static function differentiateRejectReasonType($rejectReason)
    {
        $allRejectReasonTag = self::allRejectReasonTag();

        return [
            "default" => array_values(array_intersect($allRejectReasonTag, $rejectReason)),
            "custom" => array_values(array_diff($rejectReason, $allRejectReasonTag)),
        ];
    }

    /**
     * all reject reason tag
     * @return array
     */
    private static function allRejectReasonTag()
    {
        return array_merge(... array_column(self::REJECT_REASON, "tags"));
    }
}