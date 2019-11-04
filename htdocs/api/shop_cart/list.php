<?php

/**
 * 获取购物车列表
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$json = array("status"=>1,"message"=>"","data"=>"");
session_write_close();//关闭session
$big_type= filter_input(INPUT_GET, 'bt', FILTER_SANITIZE_NUMBER_INT);


if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}
//默认是1
empty($big_type) && $big_type =1;

$params = array("username"=>$login_user_name,"domain_id"=>$system_domain,"big_type"=>$big_type);

$result = QImgShopCart::getList($params);

//获取用户信息--判断是否有下载权限
$userinfo_rs = QImgPersonal::getUserInfo(['username'=> $login_user_name]);
if(array_intersect($userinfo_rs['role'], ['design','admin','super_admin'])){
    $download_permission = true;
}

$list = array();
foreach($result as $k=>$current){

    $tmp = array();
    $tmp['sc_id'] =$current['sc_id'];
    $tmp['eid'] =$current['eid'];
    $tmp['width'] =$current['width'];
    $tmp['height'] =$current['height'];
    $tmp['title'] =$current['title'];
    $tmp['ext'] =$current['ext'];
    $tmp['download_permission'] = $download_permission || $v['username'] == $login_user_name ? true : false;


    $tmp['origin_img'] =  QImg::getImgUrl($current['url'],$system_domain,"inner_domain");

    $tmp['big_img'] =  QImg::getImgUrlResize(array(
        "img"=>$current['url'],
        "width"=>$current['width'],
        "height"=>$current['height'],
        "r_width"=>800,
        "r_height"=>0,
        'system_domain'=>$system_domain,
        "in"=>"inner_domain"));

    $tmp['small_img'] =  QImg::getImgUrlResize(array(
        "img"=>$current['url'],
        "width"=>$current['width'],
        "height"=>$current['height'],
        "r_width"=>210,
        "r_height"=>210,
        'system_domain'=>$system_domain,
        "in"=>"inner_domain"));
    $list[] = $tmp;
}

$json = array("status"=>0,"message"=>"","data"=>array());
$json['data']['list'] = array();
if($list){
    $json['data']['list'] = $list;
}
$params = array("username"=>$login_user_name,"domain_id"=>$system_domain);
$result = QImgShopCart::getListCount($params);

$counts = array();
foreach($result as $k=>$value){
    $counts[$value['big_type']] = $value['count'];
}


$json['data']['count'] = array();
foreach($dic_img['big_type'] as $k=>$value){
    $tmp = array();
    $tmp['type'] = $value;
    $tmp['big_type'] = $k;
    $tmp['num'] = isset($counts[$k]) ?$counts[$k] :"0" ;
    $json['data']['count'][] = $tmp;
}


$json['status'] = 0;
$json['message'] = "";
display_json_str_common($json,$callback);