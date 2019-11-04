<?php

/**
 * 管理员页面-活动任务管理 列表查询suggest搜索记录
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
$field_name = filter_input(INPUT_GET, 'field_name', FILTER_SANITIZE_STRING);
$query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_NUMBER_INT);

$select_params = [
    'field_name' => $field_name,
    'query' => $query,
];

$list = QSuggest::getSuggest($select_params);

QActivity_ReleaseManage::display_result(['errorCode'=>0,'data'=>$list]);