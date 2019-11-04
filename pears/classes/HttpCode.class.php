<?php

/**
 * 返回错误码
 */

interface HttpCode
{
    /**
     * 操作成功
     */
    const OK = 0;

    /**
     * 操作失败
     */
    const ERROR = -1;

    /**
     * 无权限
     */
    const PERMISSION_DENIED = -9;

    /**
     * 未登录
     */
    const NOT_LOGIN = 100;

    /**
     * 参数错误
     */
    const PARAMETER_ERROR = 101;

    /**
     * 状态码默认message
     */
    const HTTP_CODE_MESSAGE = [
        self::OK => '操作成功',
        self::ERROR => '操作失败',
        self::PERMISSION_DENIED => '您没有权限执行此操作',
        self::NOT_LOGIN => '未登录',
        self::PARAMETER_ERROR => '参数错误',
    ];
}