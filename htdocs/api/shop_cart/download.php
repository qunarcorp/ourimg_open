<?php

/**
 *  购物车批量下载
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$eIdsStr = filter_input(INPUT_GET, 'eids', FILTER_SANITIZE_STRING);

must_login();

$eIds = explode(",", $eIdsStr);


DB::TransBegin();
try{
    $imgCollect = (array) QImgShopCart::getList([
        "username" => $login_user_name,
        "domain_id" => $system_domain,
        "big_type" => null,
        "eids" => $eIds,
        "is_del" => "all"
    ]);
    
    //验证用户权限
    $params_authcheck = [
        'img_arr' => $imgCollect,
        'download_username' => $login_user_name,
    ];
    QDownloadAuth::returnAuthCheckNew($params_authcheck);
    
    $qImgDownloadInstance = new QImgDownload();
    $imgUrl = $qImgDownloadInstance->download($imgCollect, "inner_domain");
    $eIds = array_column($imgCollect, "sc_id");
}catch (\QImgApiException $e) {
    DB::TransRollback();
    $qImgDownloadInstance->clearDownloadFile();
    error_return($e->getMessage());
}
$imgEids = array_column($imgCollect, "eid");
//清除购物车
QImgShopCart::del($login_user_name, $eIds);
DB::TransCommit();
success_return("生成成功", [
    "img_url" => $imgUrl,
    'only_edit_purpose' => QImg::onlyEditPurpose($imgEids),
]);