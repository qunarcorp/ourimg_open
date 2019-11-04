<?php

/**
 * 个人删除图片
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
require_once __DIR__."/ImgValidator.php";
session_write_close();//关闭session
$urlParams = json_decode(file_get_contents("php://input"),true);

must_login();
$imgValidate = ImgValidator::delImg();
if (! $imgValidate->pass([
    "eids" => DB::EscapeString(trim($urlParams["eids"], ",")),
])) {
    error_return($imgValidate->getFirstError());
}

try{
    QImgOperate::del(explode(",", trim($urlParams["eids"], ',')), $login_user_name, false);
    success_return("操作成功");
}catch (\QImgApiException $e){
    error_return($e->getMessage());
}