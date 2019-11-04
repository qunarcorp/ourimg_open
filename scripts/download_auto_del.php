<?php

/**
 * 定时删除下过的文件，每文件保留2小时。在下载时定义
 * * * * *
 */
require_once __DIR__."/../htdocs/app_api.php";

crontab_run_one("crontab","download_auto_del_mark");

QLog::info("crontab","download_auto_del","bgn");
$min_id = 0;
$sql = "select id,filename,file_expire_date from public.download where is_del='f' and file_expire_date<current_timestamp and id >{$min_id} order by id asc  limit 1";

while($row= DB::GetQueryResult($sql,true)){

    $min_id = $row['id'];
    $sql = "select id,filename,file_expire_date from public.download where is_del='f' and file_expire_date<current_timestamp and id >{$min_id} order by id asc  limit 1";

    $expire_time = max(10,strtotime($row['file_expire_date'])-time());

    if($row['filename']) {
        $return_var = Storage::del($row['filename'],$expire_time);
        if ($return_var) {
            $result = "执行shell失败";
        } else {
            $result = "执行shell成功";
        }
    }else{
        $del_error = "文件名为空404 Not Found";
    }
    QLog::info("crontab","download_auto_del","id:{$row['id']};result:$result;return_var:{$return_var};error:{$del_error};output:".json_encode($return_var,JSON_UNESCAPED_UNICODE));

    if($return_var || preg_match("/404 Not Found/",$del_error)){
        $update = array();
        $update["is_del"] = "t";
        $update["del_time"] = date("Y-m-d H:i:s");
        $rs = DB::Update("public.download",$row['id'],$update,"id");
        QLog::info("crontab","download_auto_del","id:{$row['id']};update:".($rs?1:0));
    }else{
        QLog::info("crontab","download_auto_del","id:{$row['id']};update:".($rs?1:0));
    }
}
QLog::info("crontab","download_auto_del","end");

