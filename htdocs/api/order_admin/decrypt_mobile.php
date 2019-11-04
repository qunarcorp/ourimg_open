<?php

/**
 * 
 * 手机号解密接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$url_params = json_decode(file_get_contents("php://input"),true);

$decrypt_mobile_rs = Utility::decryptMobile($url_params['mobile']);

if( !$decrypt_mobile_rs ){
    $rs = [
        'status' => 1015,
        'message' => '解密失败',
    ];
}else{
    $rs = [
        'status' => 0,
        'data' => [
            'decrypt_mobile' => $decrypt_mobile_rs,
        ],
    ];
}


display_json_str_common($rs);
