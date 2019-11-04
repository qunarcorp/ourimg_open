<?php
/**
 * 数据库相关配置
 */
$value = array();
$value = array();
//img库 数据库采用postgresql
$value['db'] = array (
    'host' => '',//数所库host
    'user' => '',//用户名
    'pass' => '',//密码
    'name' => '',//库
);
//img只读
$value['db_read'] = array (
    'host' => '',//数所库host
    'user' => '',//用户名
    'pass' => '',//密码
    'name' => '',//库
);

//memcached
$value['memcached'] = array (
    array('127.0.0.1', 11211, 100),
);