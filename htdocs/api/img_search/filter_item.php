<?php

/**
 * list页面筛选项
 */
require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session
$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

$mch_key = 'all_countrys';
$country_rs = Mcached::get(md5($mch_key));
if( !$country_rs ){
    $country_rs = QImgInfo::getLocations();
    Mcached::set(md5($mch_key), $country_rs,600);
}

$rs = [
    'ret' => true,
    'data' => [
        'big_type' => $dic_img['big_type'],
        'time_list' => $dic_img['time_list'],
        'ext' => $dic_img['ext'],
        'purpose' => $dic_img['purpose'],
        'size_type' => $dic_img['size_type'],
        'small_type' => $dic_img['small_type'],
        'country' => $country_rs['info'],
        ],
];
display_json_str_common($rs, $callback);