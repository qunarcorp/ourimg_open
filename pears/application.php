<?php
/* for rewrite or iis rewrite */
if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
} else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
	$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
}
/* end */

error_reporting(E_ALL^E_WARNING^E_NOTICE);

define('SYS_TIMESTART', microtime(true));
define('SYS_REQUEST', isset($_SERVER['REQUEST_URI']));
define('DIR_SEPERATOR', strstr(strtoupper(PHP_OS), 'WIN')?'\\':'/');
define('DIR_ROOT', str_replace('\\','/',dirname(dirname(__FILE__))));
define('DIR_LIBARAY', DIR_ROOT . '/pears/library');
define('DIR_CLASSES', DIR_ROOT . '/pears/classes');
define('DIR_FUNCTION', DIR_ROOT . '/pears/function');
define('SYS_MAGICGPC', get_magic_quotes_gpc());

require_once DIR_ROOT.'/conf/get_configure.php';
define('DIR_CONFIGURE', DIR_ROOT . '/conf/'.SYS_CONFIGURE_PATH);

define('WWW_ROOT', rtrim(DIR_ROOT.'/htdocs')); //去掉了“/”,引人的历史文件中如果用到了，确认一下是否正确
define('SCRIPT_ROOT', DIR_ROOT . '/scripts');
define('DIR_LOG', '/var/log/');

define('REQUEST_METHOD', strtoupper($_SERVER['REQUEST_METHOD']));
define('IS_GET', REQUEST_METHOD =='GET' ? true : false);
define('IS_POST', REQUEST_METHOD =='POST' ? true : false);
define('IS_PUT', REQUEST_METHOD =='PUT' ? true : false);
define('IS_DELETE', REQUEST_METHOD =='DELETE' ? true : false);

/* encoding */
mb_internal_encoding('UTF-8');
/* end */

spl_autoload_register(function($class_name)
{
    $file_name = trim(str_replace('_', '/', $class_name), '/') . '.class.php';
    $file_path = DIR_LIBARAY . '/' . $file_name;
    if (file_exists($file_path)) {
        return require_once( $file_path );
    }
    $file_path = DIR_CLASSES . '/' . $file_name;
    if (file_exists($file_path)) {
        return require_once( $file_path );
    }
    return false;
});


function import($funcpre) {
    $file_path = DIR_FUNCTION. '/' . $funcpre . '.php'; 
    if (file_exists($file_path) ) {
        require_once( $file_path );
    }
}

/* import */
import('common');

/* ob_handler */
if(SYS_REQUEST){ ob_get_clean(); ob_start(); }
/* end ob */