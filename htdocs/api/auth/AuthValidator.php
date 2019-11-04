<?php

/**
 * 添加管理员校验
 */

class AuthValidator
{
    /**
     * /api/auth/add_power.php 参数验证
     * @return QValidator
     */
    public static function storeManager()
    {
        return \QValidator::make([
            "role" => "require|in:admin,design",
            "username" => "require",
        ], [
            "role.require" => "role必传",
            "role.in" => "role错误",
            "username.require" => "username必传",
        ]);
    }

    /**
     * /api/auth/manager.php 参数验证
     * @return QValidator
     */
    public static function managerSearch()
    {
        return \QValidator::make([
            "current" => "integer|gt:0",
            "pageSize" => "integer|between:1,100",
            "role" => "require|in:admin,design",
        ], [
            "current.integer" => "currentPage参数错误",
            "current.gt" => "currentPage参数错误",
            "pageSize.integer" => "pageSize参数错误",
            "pageSize.between" => "pageSize只能在1到100之间",
            "role.require" => "role必传",
            "role.in" => "role错误",
        ]);
    }

    /**
     * /api/auth/remove_power.php 参数验证
     * @return QValidator
     */
    public static function removePower()
    {
        return \QValidator::make([
            "username" => "require",
            "role" => "require|in:admin,design",
        ], [
            "username.require" => "username必传",
            "role.require" => "role必传",
            "role.in" => "role错误",
        ]);
    }

    /**
     * /api/auth/employee.php 参数验证
     * @return QValidator
     */
    public static function employeeSearch()
    {
        return \QValidator::make([
            "page" => "integer|gt:0",
            "pageSize" => "integer|between:1,100",
        ], [
            "page.integer" => "page参数错误",
            "page.gt" => "page参数错误",
            "pageSize.integer" => "pageSize参数错误",
            "pageSize.between" => "pageSize只能在1到100之间",
        ]);
    }

    /**
     * /api/auth/company_dept.php 参数验证
     * @return QValidator
     */
    public static function companyDept()
    {
        return \QValidator::make([
            "role" => "in:admin,design",
        ], [
            "role.in" => "role错误",
        ]);
    }
}