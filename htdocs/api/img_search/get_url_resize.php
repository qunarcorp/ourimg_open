<?php

/**
 * 生成图片链接
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

//参数获取
$width = filter_input(INPUT_GET, 'width', FILTER_SANITIZE_NUMBER_INT);
$height = filter_input(INPUT_GET, 'height', FILTER_SANITIZE_NUMBER_INT);
$eid = filter_input(INPUT_GET, 'eid', FILTER_SANITIZE_NUMBER_INT);

//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

//获取图片信息
$params_img = [
    'eid' => $eid,
];
$imgs = QImgSearch::getImgs($params_img);
if( !$imgs && is_array($imgs) ){
    $rs = [
        "ret" => false,
        "msg" => "查询失败",
        "data" => [],
    ];
    display_json_str_common($rs, $callback);
}

//获取图片需要返回的字段
$img_deal = QImgSearch::dealImgInfos($imgs);
$img_info = $img_deal[0];

//验证用户权限
$params_authcheck = [
    'callback' => $callback,
    'upload_username' => $img_info['username'],
    'download_username' => $login_user_name,
];
QDownloadAuth::returnAuthCheck($params_authcheck);

//根据尺寸获取url
$params = [
    'url' => $img_info['url'],
    'width' => $width,
    'height' => $height,
    'origin_width' => $img_info['width'],
    'origin_height' => $img_info['height'],
];

$url_rs = QImgPersonal::getImgUrl($params);

QImgDownload::addDownloadHistory($login_user_name, $img_info['id']);
QImgOperate::addDownloadNum($img_info['id']);

//计算积分
$params_point = [
    'username' => $img_info['username'],//积分用户
    'operate_username' => $login_user_name,//操作用户
    'img_id' => $img_info['id'],//操作图片id
    'operate_type' => 'download',//操作图片类型
];
$task_rs = QImgPoints::pointsDeal($params_point);

$rs = [
    "ret" => true,
    "msg" => "生成成功",
    "data" => [
        'url_resize' => $url_rs,
        'only_edit_purpose' => QImg::onlyEditPurpose($eid),
    ],
];
display_json_str_common($rs, $callback);