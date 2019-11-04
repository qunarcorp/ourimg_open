<?php

/**
 * 积分排行榜接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

$points_board = QPointInfo::getPointBoard();

$rs = [
    "status" => 0,
    "message" => "查询成功",
    "points_board" => $points_board,
];
display_json_str_common($rs, $callback);
