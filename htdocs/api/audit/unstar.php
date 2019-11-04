<?php

/**
 * 图片取消精选
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$url_params = json_decode(file_get_contents("php://input"), true);
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

$eid = $url_params["eid"];

if (! is_array($eid) || empty($eid)) {
    error_return("图片eid必填");
}

$feature_params = [
    'eid' => $eid,
    'type' => 'unrecommend',
];
$feature_rs = QAudit_Feature::featureRecommend($feature_params);

display_json_str_common($feature_rs, $callback);
