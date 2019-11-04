<?php

/**
 * 拍摄地点联动查询
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';

$callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);

$country = filter_input(INPUT_GET, 'country', FILTER_SANITIZE_STRING);
$province = filter_input(INPUT_GET, 'province', FILTER_SANITIZE_STRING);
$city = filter_input(INPUT_GET, 'city', FILTER_SANITIZE_STRING);

$params = [
    'country' => $country,
    'province' => $province,
    'city' => $city,
];
$location_rs = QImgInfo::getLocations($params);
if( !$location_rs ){
    $rs = [
        "ret" => false,
        "msg" => "查询失败",
        "data" => [],
    ];
}else{
    $rs = [
        "ret" => true,
        "msg" => "查询成功",
        "data" => $location_rs,
    ];
}

display_json_str_common($rs, $callback);