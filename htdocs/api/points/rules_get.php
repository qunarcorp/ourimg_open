<?php

/**
 * 查询积分规则
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();


$rules_rs = QPointRule::getPointRules();
$rules_rs['point_questions'] = json_decode($rules_rs['point_questions'], true);

$point_questions = [];
foreach( $rules_rs['point_questions'] as $k => $v ){
    $point_questions[$v['number']] = $v;
}
ksort($point_questions);
$rules_rs['point_questions'] = array_values($point_questions);

$rs = [
    'status' => $rules_rs ? 0 : 107,
    'data' => [
        'rules_info' => $rules_rs,
    ],
    'message' => $rules_rs ? '操作成功' : '操作失败',
];


display_json_str_common($rs);
