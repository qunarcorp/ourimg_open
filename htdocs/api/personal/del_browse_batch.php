<?php

/**
 * 删除我的浏览足迹：批量删除和全部清空
 * 清空数据不清img表的数量
 * eid使用英文逗号,分割
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$url_params = json_decode(file_get_contents("php://input"),true);
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$del_type = $url_params['del_type'] ? $url_params['del_type'] : '';
$eid = $url_params['eid'] ? $url_params['eid'] : '';
if( $eid ){
    $eid_arr = explode(",", $eid);
}else{
    $eid_arr = [];
}

//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

$params = [
    'del_type' => $del_type,
    'eid' => $eid_arr,
    'username' => $login_user_name,
];

$del_rs = QImgMyBrowse::delMyBrowses($params);
if( !$del_rs || !$del_rs['ret'] ){
    $rs = [
        "ret" => false,
        "msg" => $del_rs['msg'],
    ];
}else{
    $rs = [
        "ret" => true,
        "msg" => "操作成功",
    ];
}

display_json_str_common($rs, $callback);