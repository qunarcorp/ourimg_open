<?php

/**
 * 设置已读
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$json = array("status"=>1,"message"=>"","data"=>array());
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
//已读 t，未读，f 默认
$read = filter_input(INPUT_GET, 'read', FILTER_SANITIZE_STRING);

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);


if(empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}


if(empty($id)){
    //未登录
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}

if(!QImgMessage::read($id,$login_user_name)){
    $json['status'] = 107;
    $json['message'] = "操作失败";
    display_json_str_common($json,$callback);
}

$json['status'] = 0;
$json['message'] = "操作成功";
display_json_str_common($json,$callback);