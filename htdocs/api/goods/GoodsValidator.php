<?php

/**
 *
 */

class GoodsValidator
{
    /**
     * /api/goods/store.php 参数验证
     * @return QValidator
     */
    public static function store()
    {
        \QValidator::extend("date_section", function($value, $input) {
            if (! is_null($input["exchange_begin_time"])
                && ! is_null($input["exchange_end_time"])) {
                return strtotime($input["exchange_begin_time"]) < strtotime($input["exchange_end_time"]);
            }

            return true;
        });

        \QValidator::extend("date_valid", function($value, $input) {
            return !! $input["exchange_begin_time"] == !! $input["exchange_end_time"];
        });

        return \QValidator::make([
            "title" => "require|length:1,255",
            "description" => "require|length:1,500",
            "img_url" => "require|array",
            "exchange_begin_time" => "date|date_valid",
            "exchange_end_time" => "date|date_section",
            "exchange_description" => "require|length:1,500",
            "price" => "require|number|between:0.01,100000",
            "points" => "require|integer|between:1,100000",
            "stock" => "require|integer|between:1,100000",
            "detail" => "require|length:1,500",
            "detail_title" => "require|length:1,500",
            "detail_img" => "require|array",
        ], [
            "title.require" => "商品名称必填",
            "title.length" => "商品名称长度应在1~255个字之间",
            "description.require" => "商品描述必填",
            "description.length" => "商品描述长度应在1~500个字之间",
            "img_url.require" => "商品图片必填",
            "img_url.array" => "商品图片格式错误",
            "exchange_begin_time.date" => "兑换开始时间格式错误",
            "exchange_begin_time.date_valid" => "兑换时间格式错误",
            "exchange_end_time.date" => "兑换结束时间格式错误",
            "exchange_end_time.date_section" => "兑换结束时间需大于兑换开始时间",
            "exchange_description.require" => "兑换说明必填",
            "price.require" => "商品价格必填",
            "price.number" => "商品价格格式错误",
            "price.between" => "商品价格必须在0.01~100000之间",
            "points.require" => "商品兑换积分必填",
            "points.number" => "商品兑换积分格式错误",
            "points.between" => "商品兑换积分必须在1~100000之间",
            "stock.require" => "商品库存必填",
            "stock.number" => "商品库存格式错误",
            "stock.between" => "商品库存必须在1~100000之间",
            "detail.require" => "商品详情必填",
            "detail_title.require" => "商品详情标题必填",
            "detail_title.length" => "商品详情标题长度应在1~255个字之间",
            "detail_img.require" => "商品详情图片必填",
            "detail_img.array" => "商品详情图片格式错误",
        ]);
    }

    /**
     * /api/goods/update.php 参数验证
     * @return QValidator
     */
    public static function update()
    {
        \QValidator::extend("date_section", function($value, $input) {
            if (! is_null($input["exchange_begin_time"])
                && ! is_null($input["exchange_end_time"])) {
                return strtotime($input["exchange_begin_time"]) < strtotime($input["exchange_end_time"]);
            }

            return true;
        });

        \QValidator::extend("date_valid", function($value, $input) {
            return !! $input["exchange_begin_time"] == !! $input["exchange_end_time"];
        });

        return \QValidator::make([
            "eid" => "require|integer|gt:0",
            "title" => "require|length:1,255",
            "description" => "require|length:1,500",
            "img_url" => "require|array",
            "exchange_begin_time" => "date|date_valid",
            "exchange_end_time" => "date|date_section",
            "exchange_description" => "require|length:1,500",
            "price" => "require|number|between:0.01,100000",
            "points" => "require|integer|between:1,100000",
            "stock" => "require|integer|between:1,100000",
            "detail" => "require|length:1,500",
            "detail_title" => "require|length:1,500",
            "detail_img" => "require|array",
        ], [
            "eid.require" => "商品eid必传",
            "eid.integer" => "商品eid格式错误",
            "eid.gt" => "商品eid格式错误",
            "title.require" => "商品名称必填",
            "title.length" => "商品名称长度应在1~255个字之间",
            "description.require" => "商品描述必填",
            "description.length" => "商品描述长度应在1~500个字之间",
            "img_url.require" => "商品图片必填",
            "img_url.array" => "商品图片格式错误",
            "exchange_begin_time.date" => "兑换开始时间格式错误",
            "exchange_begin_time.date_valid" => "兑换时间格式错误",
            "exchange_end_time.date" => "兑换结束时间格式错误",
            "exchange_end_time.date_section" => "兑换结束时间需大于兑换开始时间",
            "exchange_description.require" => "兑换说明必填",
            "exchange_description.length" => "兑换说明长度应在1~500个字之间",
            "price.require" => "商品价格必填",
            "price.number" => "商品价格格式错误",
            "price.between" => "商品价格必须在0.01~100000之间",
            "points.require" => "商品兑换积分必填",
            "points.number" => "商品兑换积分格式错误",
            "points.between" => "商品兑换积分必须在1~100000之间",
            "stock.require" => "商品库存必填",
            "stock.number" => "商品库存格式错误",
            "stock.between" => "商品库存必须在1~100000之间",
            "detail.require" => "商品详情必填",
            "detail.length" => "商品详情长度应在1~500个字之间",
            "detail_title.require" => "商品详情标题必填",
            "detail_title.length" => "商品详情标题长度应在1~255个字之间",
            "detail_img.require" => "商品详情图片必填",
            "detail_img.array" => "商品详情图片格式错误",
        ]);
    }

    /**
     * /api/goods/info.php 参数验证
     * @return QValidator
     */
    public static function info()
    {
        return \QValidator::make([
            "eid" => "require|integer|gt:0",
        ], [
            "eid.require" => "商品eid必传",
            "eid.integer" => "商品eid格式错误",
            "eid.gt" => "商品eid格式错误",
        ]);
    }
}