<?php

/**
 * 下载图片 可提供多个id供下载
 */

require_once __DIR__."/../app_api.php";
session_write_close();//关闭session
$eid= filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_STRING);

//验证用户登录
QImgPersonal::checkUserLoginNew();

DB::TransBegin();
try{
    $imgCollect = (array) QImg::getInfo([
        "eid" => $eid,
        "audit_state" => 2,
        "is_del" => "f"
    ]);
    
    //验证用户权限
    $params_authcheck = [
        'upload_username' => $imgCollect['username'],
        'download_username' => $login_user_name,
    ];
    QDownloadAuth::returnAuthCheckNew($params_authcheck);
    
    $qImgDownloadInstance = new QImgDownload();
    $imgUrl = $qImgDownloadInstance->download(empty($imgCollect) ? [] : [$imgCollect], "inner_domain");
}catch (\QImgApiException $e) {
    DB::TransRollback();
    $qImgDownloadInstance->clearDownloadFile();
    error_return($e->getMessage());
}

DB::TransCommit();
success_return("生成成功", [
    "img_url" => $imgUrl,
    'only_edit_purpose' => QImg::onlyEditPurpose($eid),
]);