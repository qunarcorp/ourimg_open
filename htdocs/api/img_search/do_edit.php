<?php

/**
 * 将 已通过|已拒绝|审核中的图片打回草稿箱
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$json = array("status"=>1,"message"=>"","data"=>array());
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
//已读 t，未读，f 默认
$read = filter_input(INPUT_GET, 'read', FILTER_SANITIZE_NUMBER_INT);

$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_NUMBER_INT);


if(empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

if(empty($eid) || !is_numeric($eid)){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}

/**
 * 获取图片信息
 */
$img_info = QImg::getInfo(array("eid"=>$eid,'username'=>$login_user_name,"is_del"=>"f"));

if(!$img_info){
    //不存在，或者图片的用户名不是当前等录的用户
    $json['status'] = 101;
    $json['message'] = "图片不存在";
    display_json_str_common($json,$callback);
}


$up_data = array();
$up_data['audit_state']=0;
$up_data['audit_time']=date("Y-m-d H:i:s");


$up_where = array();
$up_where['id'] = $img_info['id'];
$up_where['username'] = $login_user_name;

DB::TransBegin();
$update_rs = QImg::update($up_where,$up_data);

if(!$update_rs){
    DB::TransRollback();
    $json['status'] = 107;
    $json['message'] = "操作失败";
    display_json_str_common($json,$callback);
}

//计算积分
$params_point = [
    'username' => $img_info['username'],//积分用户
    'operate_username' => $login_user_name,//操作用户
    'img_id' => $img_info['id'],//操作图片id
    'audit_state' => $img_info['audit_state'],//操作图片当前状态：本次操作原状态为审核通过，要扣分
    'operate_type' => 'edit',//操作图片类型
];
$task_rs = QImgPoints::pointsDeal($params_point);
if( !$task_rs ){
    DB::TransRollback();
    $json['status'] = 107;
    $json['message'] = "操作失败";
    display_json_str_common($json,$callback);
}

DB::TransCommit();

$json['status'] = 0;
$json['message'] = "操作成功";
display_json_str_common($json,$callback);