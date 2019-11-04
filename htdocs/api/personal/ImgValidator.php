<?php

/**
 * 删除图片校验
 */

class ImgValidator
{
    /**
     * /api/auth/add_power.php 参数验证
     * @return QValidator
     */
    public static function delImg()
    {
        return \QValidator::make([
            "eids" => "require",
        ], [
            "eids.require" => "请选择需要删除的图片",
        ]);
    }
}