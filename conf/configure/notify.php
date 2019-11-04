<?php
/**
 * 报警 提醒 相关设置
 *
 * 所有提醒分类 可设置 qtalk|mail
 */
$value = [];
# 积分兑换提醒
$value['point_exchange_tip'] = [
    # 发送的类型
    "type" => "mail",
    # 接收的用户qt消息不支持群组 如果是邮箱 ，格式:xx@test.com
    "to" => array(""),
    # 标题
    "subject" => "图片系统：积分商城兑换",
    # 消息内容模板
    "body" => "用户:{login_user_name}兑换成功，请注意发货",
];

# db报警 用于连接失败，sql执行失败等
$value['db_alert'] = [
    "type" =>"mail",
    "to" =>[''],
    "subject" => "{title}",
    "body" => "{body}",
    # 报警频率 半小时报一次 1800s
    "frequency"=>1800,
];

# 用于api类调用错误通用报警
$value['api_error'] = [
    "type" =>"mail",
    "to" =>[''],
    "subject" => "{title}",
    "body" => "{body}",
    # 报警频率 半小时报一次 1800s
    "frequency"=>1800,
];