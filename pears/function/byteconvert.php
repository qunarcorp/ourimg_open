<?php

/**
 * 字节数转换成字符串
 * @param $bytes
 * @return mixed
 */
function  byte_convert($bytes){

    $s = array('B', 'Kb', 'MB', 'GB', 'TB', 'PB');
    $e = floor(log($bytes,1024));

    return sprintf('%.1f '.$s[$e], ($bytes/pow(1024, floor($e))));
}
