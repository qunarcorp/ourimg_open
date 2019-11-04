<?php
/**
 * 邮件发送相关
 *
 *  pears/classes/QNotify/Mailer.class.php
 */
$value = [
    # 使用系统自带的mail发送 mail|smtp|sendmail|qmail
    'mail' => 'smtp',
    'encoding' => 'utf-8',
    'smtp_auth' =>false,
    'host' => '',//smtp host
    'port' => '25',
    # ssl tls
    'ssl' => '',
    'user' => '',
    'pass' => '',
    'from' => '',//发送方 邮件地址
    'from_name' => ''//发送方名称
];