<?php

/**
 * 获取消息总数
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$json = array("status"=>1,"message"=>"","data"=>array("count"=>0));
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
//已读 t，未读，f 默认
$read = filter_input(INPUT_GET, 'read', FILTER_SANITIZE_STRING);


!in_array($read,array("t",'f')) && $read = 'f';

if(empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

$count = QImgMessage::count($login_user_name,$read);
$json['status'] = 0;
$json['data']['count'] = $count;
display_json_str_common($json,$callback);