<?php

/**
 * 删除购物车eids 购物车的eid
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$json = array("status"=>1,"message"=>"","data"=>"");

$ids= filter_input(INPUT_GET, 'ids', FILTER_SANITIZE_STRING);


if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

if(empty($ids)){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}

$ids = explode(",",$ids);
foreach($ids as $id){
    if(!is_numeric($id)){
        $json['status'] = 101;
        $json['message'] = "参数错误";
        display_json_str_common($json,$callback);
    }
}
if(!QImgShopCart::del($login_user_name,$ids)){
    $json['status'] = 107;
    $json['message'] = "删除数据失败";
    display_json_str_common($json,$callback);
}

$json['status'] = 0;
$json['message'] = "删除成功";
display_json_str_common($json,$callback);
