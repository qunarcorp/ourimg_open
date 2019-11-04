<?php

/**
 * 晶志类
 * Class QLog
 */

class QLog
{
    /**
     * debug      debug 详情
     * info       重要事件  例如：用户登录和SQL记录。
     * notice     一般性重要的事件。
     * warning    出现非错误性的异常。
     * error      运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
     * critical   紧急情况   例如：程序组件不可用或者出现非预期的异常。
     * alert      **必须** 立刻采取行动  例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下， **应该** 发送一条警报短信把你叫醒。
     * emergency  系统不可用
     */
    const ALLOW_LOG_LEVEL = ["debug", "info", "notice", "warning", "error", "critical", "alert", "emergency"];
    
    /*
     * @param type $filename指定文件名：比如monitor_framework.log
     * @param type $level 记录信息的级别：error，warn，info，debug
     * @param type $msg具体信息
     */
    public static function write($prefix, $action, $message, $level)
    {
        global $INI;
        $log_path = $INI['stats']['statslogpath'].$prefix."_".$action.DIRECTORY_SEPARATOR;

        if(!is_dir($log_path)){
            mkdir($log_path, 0777, true);
        }

        $date_str = date("Y-m-d");
        $log_file = $log_path.$date_str.".log";

        $message = "[".date("Y-m-d H:i:s")."] [$level]:".$message."\n";
        error_log($message, 3, $log_file);
    }

    public static function __callStatic($name, $arguments)
    {
        self::write($arguments[0], $arguments[1], $arguments[2], $name);
    }

}