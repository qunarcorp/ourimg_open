<?php

/**
 * 个人主页-我的上传
 * 审核状态：0--待提交,1 待审核,--2审核通过,3--审核驳回
 * big_type:1图片,2矢量图,3--PSD,4--PPT模板
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

//参数获取
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_SANITIZE_STRING);
$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
$big_type = filter_input(INPUT_GET, 'big_type', FILTER_SANITIZE_NUMBER_INT);
$audit_state = filter_input(INPUT_GET, 'audit_state', FILTER_SANITIZE_NUMBER_INT);
$keyword = filter_input(INPUT_GET, 'keyword', FILTER_SANITIZE_STRING);
$keyword = html_encode($keyword);
$keyword = array_filter(array_unique(explode(",", $keyword)));

//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);
$username = $login_user_name;

//我的或他人的个人主页，需要获取用户信息
$userinfo_rs = QImgPersonal::getUserInfo(['username'=> $username]);

if (empty($sort_by)) {
    if ($audit_state == 0) {// 草稿箱
        $sort_by = 7;// 上传时间
    }elseif (in_array($audit_state, [1, 2, 3])) {// 审核中、未通过、已通过
        $sort_by = 1;// 审核时间
    }else{
        $sort_by = null;
    }
}

//获取图片数组
$params = [
    "username" => $username?$username:'',
    "sort_by" => $sort_by,
    "audit_state" => strlen($audit_state) > 0 ? $audit_state : 'all',
    "offset" => $offset,
    "limit" => $limit,
    "keyword" => $keyword,
    "big_type" => $big_type ? $big_type : 1,
];

$imgs = QImgSearch::getImgs($params);
if( !$imgs ){
    $rs = [
        "ret" => true,
        "msg" => "查询失败",
        "data" => [],
        "count" => 0,
        "userinfo" => $userinfo_rs,
    ];
    display_json_str_common($rs, $callback);
}

//处理图片数组返回参数
$big_url_type = 'my_upload';//返回不加logo大图
$deal_imgs = QImgSearch::dealImgInfos($imgs, ['username' => $username,'big_url_type' => $big_url_type]);
$rs = [
    "ret" => true,
    "msg" => "查询成功",
    "data" => $deal_imgs,
    "count" => QImgSearch::getImgCount($params),
    "userinfo" => $userinfo_rs,
];
display_json_str_common($rs, $callback);