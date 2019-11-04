<?php
/**
 * 配置登录认证 相关的
 */

$value = [];
# 支持的登录认证模块
$value['auth_drives']   = ['ldap','local'];

# 登录认证使用方式 启用ldap时需注意用户名错误，帐户被锁止的情况
$value['driver']        = "ldap";

# 使用ldap 登录相关配置
$value['ldap'] = [];
# ldap 服务器ip 或域名
$value['ldap']['server_ip']         = "";
# ldap 服务器端口号
$value['ldap']['server_port']       = "389";
# 认证用户 用户搜索搜索 ，不搜索可不填
$value['ldap']['search_user']      = "";
# 认证密码 用户搜索搜索 ，不搜索可不填
$value['ldap']['search_user_password']  = "";

# 获取用户信息 搜索用户 rdn dn 值
$value['ldap']['base_dn']           = "";
# 用户所在的域 有的需要有的不需要， 组合登录使用 例：用户名:xx 登录时用 xx@test.com
$value['ldap']['user_region']       = "";
# 用户名字段 exchange samaccountname openldap 为uid
$value['ldap']['username_column']    = 'samaccountname';
# ldap 需要获取的字段
$value['ldap']['columns']    = ['uid','cn','dn','samaccountname','mail','department'];


# 系统 超级用户
$value['SUPER_ADMIN'] = [
    'xx',
];

# 如果没有头像 ，默认头像
$value['default_vcard'] = "https://qt.qunar.com/file/v2/download/perm/ff1a003aa731b0d4e2dd3d39687c8a54.png?&w=120&h=120";


# 用户登录是否自动更新本地用户 仅限 qsso ldap 模式 自动更新
$value['auto_update_local_user'] = true;
# 自动更新用户时长 1 天  此项需适用于 strtotime函数
$value['auto_update_local_user_time'] = " +1 day";
