<?php

/**
 * 消息列表
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$json = array("status"=>1,"message"=>"","data"=>array("count"=>0,"list"=>array()));

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);

//已读 t，未读，f 默认
$read = filter_input(INPUT_GET, 'read', FILTER_SANITIZE_STRING);

!in_array($read,array("t",'f')) && $read = 'f';


empty($limit) && $limit = 20;
empty($offset) && $offset = 0;

if(empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

$count = QImgMessage::count($login_user_name,$read);

//有总数才会去取列表
$list = array();
if($count){
    $list = QImgMessage::list($login_user_name,$read,$limit,$offset);

    $today = strtotime(date("Y-m-d"));
    $yesterday =strtotime(date("Y-m-d",strtotime("-1 day")));
    foreach($list as $k=>$current){
        $create_time = strtotime($current['create_time']);
        if($create_time > $today){
            //今天的
            //消息发送时间（今天 hh:mm、昨天 hh:mm、超过48小时展示具体时间：yyyy-mm-dd hh:mm）
            //create_time
            $data_format = "今天 H:i";
        }else if($create_time > $yesterday){
            //昨天的
            //消息发送时间（今天 hh:mm、昨天 hh:mm、超过48小时展示具体时间：yyyy-mm-dd hh:mm）
            $data_format = "昨天 H:i";
        }else{
            $data_format = "Y-m-d H:i";
        }
        $list[$k]['create_time'] = date($data_format,$create_time);
    }
}
$json['status'] = 0;
$json['data']['count'] = $count;
$json['data']['list'] = $list;
display_json_str_common($json,$callback);