<?php

/**
 *  认证接口定义
 * Interface Auth_Interface
 */

interface Auth_Interface
{
    /**
     * 获取登录用户名
     * @return mixed
     */
    public function getLoginUserName();
    /**
     * 认证 用户名密码
     * @return mixed
     */
    public function auth($username,$password);

    /**
     * 获取用户信息
     * @param $username
     * @return mixed
     */
    public function  getUserInfo($username);

    /**
     * 搜索用户
     * @param $user
     * @return mixed
     */
    public function searchUser(array $params);
}