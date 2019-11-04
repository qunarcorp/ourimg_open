<?php
function configure_keys() {
    return array(
        'auth',//登录相关
        'db_config',
        'mail',
        'stats',
        'system',
        'qtalk',
        'watcher',
        'notify',//通知相关的
        'storage',//存储相关
        'permission_uri',//权限
        'img_check_dubbo_qmq',//图片检测服务相关配置
    );
}

function configure_load() {
    global $INI;
    $keys = configure_keys();
    foreach($keys AS $one) {
        $INI[$one] = _configure_load($one);
    }
    return $INI;
}

function _configure_load($key=null) {
    if (!$key) return NULL;
    $php = DIR_CONFIGURE . '/' . $key . '.php';
    if ( file_exists($php) ) {
        require_once($php);
    }
    return $value;
}
