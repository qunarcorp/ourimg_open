<?php

/* import other */
import('configure');
import('byteconvert');
import('date');
import('watcher');

/**
 * URL重定向
 * @param string $url 重定向的URL地址
 * @param integer $time 重定向的等待时间（秒）
 * @param string $msg 重定向前的提示信息
 * @return void
 *
 */
function redirect($url, $time = 0 ,$msg="") {
    //多行URL地址支持
    $url = str_replace(array("\n", "\r"), '', $url);
    if (!headers_sent()) {
        // redirect
        if (0 === $time) {
            header('Location: ' . $url);
        } else {
            header('Content-Type: text/html; charset=UTF-8'); 
            header("refresh:{$time};url={$url}");
        }
    } else {
        echo "<meta charset='utf-8'><meta http-equiv='Refresh' content='{$time};URL={$url}'>";
    }

    if(!empty($msg)){
        echo $msg;
    }
    exit();
}

/* end */

/* for post trim */

function trimarray($o) {
    if (!is_array($o))
        return trim($o);
    foreach ($o AS $k => $v) {
        $o[$k] = trimarray($v);
    }
    return $o;
}

$_POST = trimarray($_POST);
/* end */

set_error_handler('error_handler');

/**
 * 公用的显示json串
 * @param array $array
 * @param string $callback
 */
function display_json_str_common($array, $callback="") {
    header('Content-Type: application/json; charset=UTF-8');
    $callback = htmlspecialchars($callback, ENT_QUOTES);
    if ($callback) {
        echo $callback . "(" . json_encode($array,JSON_UNESCAPED_UNICODE) . ")";
    } else {
        echo json_encode($array,JSON_UNESCAPED_UNICODE);
    }
    die();
}


/**
 * 接口缓存获取
 * @param $options
 * @param int $cache_time
 * @param bool $clean_cache
 * @return array|bool|mixed|缓存内容
 */
function api_request_cache_common($options,$cache_time=0,$clean_cache=false){

    if(empty($options['url'])){
        //必须有
        return false;
    }
    $result =  array();
    $cache_key = md5(serialize($options));
    !$clean_cache && $result = Mcached::get($cache_key);
    if(!$result){
        $tmp = Utility::GetHttpRequestOnlyCurl($options);
        $result  = json_decode($tmp['result'],true);
        if($cache_time){
            Mcached::set($cache_key,$result,$cache_time);
        }
    }
    return $result;
}


/*
 *
 * 定时程序只跑一次判断
 * 
 * 根据运行的脚本，以及参数，生成一个唯一的标识文件（记录当前运行程序的pid）
 * 开始运行，判断标识文件的pid 与系统进程(pas aux |grep的脚本名称|argv[0] )对比pid，
 * 如果pid 存在则本进程退出，记录下本进程的pid
 * @param string $log_prefix 记录日志的目录前缀 Qlog::info $log_prefix
 * @param string $log_action 记录日志的目录具体目录 Qlog::info $log_action
 * @global array $argv
 */

function crontab_run_one($log_prefix, $log_action) {
    //判断程序是否在运行中，脚本只运行一次
    global $argv;
    QLog::info($log_prefix, $log_action, "执行开始");

    $debug = debug_backtrace();
    $filename = $debug[count($debug) - 1]['file'];
    $filename_argv = $argv[0];
    $argv_nofile = $argv;
    array_shift($argv_nofile);


    $mark_file = sys_get_temp_dir() .DIRECTORY_SEPARATOR . md5($filename . var_export($argv_nofile, true)); //标识文件
    $lock_mark = file($mark_file);
    $lock = trim($lock_mark[0]); //存储的执行程序的pid
    $is_exec = 0;
    $_is_run = 0;

    QLog::info($log_prefix, $log_action, "filename:" . $filename);
    QLog::info($log_prefix, $log_action, "argv:" . var_export($argv, true));
    QLog::info($log_prefix, $log_action, "argv_nofile:" . var_export($argv_nofile, true));
    QLog::info($log_prefix, $log_action, "mark_file:$mark_file");

    //获取当前系统进程中是否有执行当前脚本
    exec("ps aux |grep '" . $filename . "'", $is_exec);

    QLog::info($log_prefix, $log_action, "ps:ps aux |grep '" . $filename . "'");
    QLog::info($log_prefix, $log_action, "is_exec:" . var_export($is_exec, true));

    foreach ($is_exec as $v) {
        $_csv = str_getcsv(preg_replace("/\s+/", "\t", $v), "\t");
        if ($_csv['1'] == $lock) {//如果pid相同，则表示上一个还没有执行完成
            $_is_run = 1;
            break;
        }
    }
    if (!$_is_run) {
        //argv file 有可能是全路径 也有可能不带全路径
        exec("ps aux |grep '" . $filename_argv . "'", $is_exec);

        QLog::info($log_prefix, $log_action, "ps:ps aux |grep '" . $filename_argv . "'");
        QLog::info($log_prefix, $log_action, "is_exec:" . var_export($is_exec, true));

        foreach ($is_exec as $v) {
            $_csv = str_getcsv(preg_replace("/\s+/", "\t", $v), "\t");
            if ($_csv['1'] == $lock) {//如果pid相同，则表示上一个还没有执行完成
                $_is_run = 1;
                break;
            }
        }
    }
    //判断是否正在执行中
    if ($lock && $_is_run) {
        QLog::info($log_prefix, $log_action, "pid:{$lock}文件执行中");
        exit("pid:{$lock}文件执行中");
    }
    QLog::info($log_prefix, $log_action, "cron now pid:" . getmypid());
    //获取当前系统进程中是否有执行当前脚本 END
    //锁定当前
    file_put_contents($mark_file, getmypid() . "\n");
}

function jumpto($url) {
    header('Location: ' . $url);
    die();
}


/**
 * dump die 方便测试输出
 * @param $output
 */
function dd($output)
{
    if (is_string($output) || is_numeric($output)){
        print $output;
    }else{
        echo "<pre>";
        var_dump($output);
    }
    die;
}

/**
 * 数组分组
 * @param array $arr
 * @param string $field
 * @return array
 */
function array_group(array $arr, $field)
{
    $newArr = [];
    if($arr){
        foreach ($arr as $value) {
            $newArr[$value[$field]][] = $value;
        }
    }
    return $newArr;
}

/**
 * list sort
 * @param array $list
 * @param $field
 * @param string $sort
 * @return array
 */
function list_sort(array $list, $field, $sort = 'asc')
{
    $refer = array();
    $resultSet = array();
    foreach ($list as $i => $data)
        $refer[$i] = &$data[$field];
    switch ($sort) {
        case 'asc':
            asort($refer);
            break;
        case 'desc':
            arsort($refer);
            break;
    }
    foreach ($refer as $key => $val)
        $resultSet[] = &$list[$key];
    return $resultSet;
}

/**
 * 在数组的任意位置插入一个数组
 * @param array $array
 * @param int $position
 * @param array $insertArray
 * @return array
 */
function array_insert(array $array, $position, array $insertArray)
{
    $firstArray = array_slice($array, 0, $position);
    return array_merge($firstArray, $insertArray, array_slice($array, $position));
}

/**
 * php5.5以下不支持array_column 保证兼容
 */
if (! function_exists("array_column")){
    function array_column(array $array, $column_key, $index_key=null){
        $result = [];
        foreach($array as $arr) {
            if(!is_array($arr)) continue;

            if(is_null($column_key)){
                $value = $arr;
            }else{
                $value = $arr[$column_key];
            }

            if(!is_null($index_key)){
                $key = $arr[$index_key];
                $result[$key] = $value;
            }else{
                $result[] = $value;
            }
        }
        return $result;
    }
}

/**
 * unset keys
 */
if (! function_exists('array_forget')) {
    function array_forget(&$array, $keys)
    {
        $original = &$array;
        $keys = (array) $keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (array_key_exists($key, $array)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }
}

/**
 * unset keys
 */
if (! function_exists('array_except')) {
    function array_except($array, $keys)
    {
        array_forget($array, $keys);

        return $array;
    }
}

/**
 * reserve some keys
 */
if (! function_exists('array_only')) {
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
}

if (! function_exists('array_replace_key')) {
    function array_replace_key($array, $column)
    {
        $newArray = [];
        foreach ($array as $value) {
            $newArray[$value[$column]] = $value;
        }
        return $newArray;
    }
}

/**
 * common success return
 * @param string $message
 * @param array $data
 * @param int $status
 */
function success_return($message = 'success', $data = [], $status = 0)
{
    display_json_str_common([
        "status" => $status,
        "message" => $message ? $message : (isset(HttpCode::HTTP_CODE_MESSAGE[$status]) ? HttpCode::HTTP_CODE_MESSAGE[$status] : ""),
        "data" => $data,
    ], $_GET["callback"]);
}

/**
 * common error return
 * @param string $message
 * @param array $data
 * @param int $status
 */
function error_return($message = 'error', $data = [], $status = -1)
{
    display_json_str_common([
        "status" => $status,
        "message" => $message ? $message : (isset(HttpCode::HTTP_CODE_MESSAGE[$status]) ? HttpCode::HTTP_CODE_MESSAGE[$status] : ""),
        "data" => $data,
    ], $_GET["callback"]);
}

/**
 * permission denied
 */
function no_permission()
{
    display_json_str_common([
        "status"=> HttpCode::PERMISSION_DENIED,
        "message"=>"您没有权限执行此操作！",
        "data"=>[]
    ], $_GET["callback"]);
}

/**
 * must login
 */
function must_login()
{
    QImgPersonal::checkUserLogin([
        'callback' => $_GET['callback']
    ]);
}

/**
 * array 转成 in where sql
 * @param $array
 * @return string
 */
function in_where_sql($array)
{
    return implode(",", array_map(function($item){
        return "'{$item}'";
    }, $array));
}

/**
 * db update 内容
 * @param $current
 * @param $old
 * @return array
 */
function db_row_diff($current, $old)
{
    if (empty($old)) {
        return $current;
    }

    $diff = [];
    foreach ($current as $key => $value){
        if (! array_key_exists($key, $old) || $value !== $old[$key]) {
            $diff[$key] = $value;
        }
    }

    return $diff;
}

function json_input()
{
    $params = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== 0) {
        error_return("非有效json格式.");
    }
    return $params;
}

if (! function_exists("array2pgarray")) {
    function array2pgarray($arr) {
        $arr = array_values($arr);
        $arrStr = implode(",", array_map(function($item){
            return "\"{$item}\"";
        }, $arr));

        return "{" . $arrStr . "}";
    }
}

if (! function_exists("pgarray2array")) {
    function pgarray2array($str) {
        if (! $str) {
            return [];
        }
        $str = trim(trim($str, "{"), "}");

        return explode(",", $str);
    }
}

if (! function_exists("array2insql")) {
    function array2insql($arr) {
        $arr = array_values($arr);
        $arrStr = implode(",", array_map(function($item){
            return "'{$item}'";
        }, $arr));

        return $arrStr;
    }
}

/**
 * page size
 * @return float|int
 */
function page_size()
{
    $pageSize = abs(intval($_GET["pageSize"]));

    return $pageSize ? ($pageSize > 200 ? 200 : $pageSize) : 20;
}

/**
 * current page
 * @return float|int
 */
function current_page()
{
    return abs(intval($_GET["page"])) ?: 1;
}

/**
 * 抛出异常处理
 * @param string $msg 异常消息
 * @param integer $code 异常代码 默认为0
 * @throws Exception
 * @return void
 */
function E($msg, $code=0)
{
    throw new Exception($msg, $code);
}

/**
 * encode 单引号双引号
 * @param $val
 * @return string
 */
function html_encode($val){
    return htmlspecialchars($val,ENT_QUOTES);
}