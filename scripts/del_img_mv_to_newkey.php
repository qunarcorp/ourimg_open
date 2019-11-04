<?php

/**
 * 将标记的图片移动 确认没有已存在的图片，将其移至删除目录
 * 如果将来存储不够用的时候可考虑删除的文件按时间，清除一下
 * 每分钟执行
 */


require_once __DIR__."/../htdocs/app_api.php";

crontab_run_one("crontab","del_img_mv_to_new_mark");
QLog::info("crontab","del_img_mv_to_new","bgn");


$sql = "select max(id) from public.img_del_record  where ceph_del='f' ";
$row = DB::GetQueryResult($sql,true);
$max_id = $row['max']+1;

$sql = "select id,url from public.img_del_record where ceph_del='f' and id <{$max_id} order by id desc  limit 1";


while($row= DB::GetQueryResult($sql,true)){

    $max_id = $row['id'];
    $sql = "select id,url from public.img_del_record where ceph_del='f' and id <{$max_id} order by id desc  limit 1";


    $img_sql = "select count(1) from public.img where url ='{$row['url']}' and is_del='f'";

    $result = DB::GetQueryResult($img_sql,true);
    if($result['count']>0){
        QLog::info("crontab","del_img_mv_to_new","{$row['url']}:存在未删除:{$result['count']}个");

        # 重复的删除了就不要在跑了， 等下次执行删除， 省得一直在跑这个任务记日志
        $rs2 = DB::Update("public.img_del_record",$row['id'],array("ceph_del"=>"t"),"id");
        if(!$rs2){
            QLog::info("crontab","del_img_mv_to_new","{$row['url']}:存在未删除:{$result['count']}个;更新img_del_record表，ceph_del=t:失败");
            continue;
        }
    }
    //复制至删除目录
    $newfile = "delete/".$row['url'];
    $aws_copy = Storage::copy($row['url'],$newfile);

    QLog::info("crontab","del_img_mv_to_new","{$row['url']}:复制至新key:$newfile");
    if($aws_copy['ObjectURL']){
        QLog::info("crontab","del_img_mv_to_new","{$row['url']}:复制至新key:$newfile;成功");
    }else{
        QLog::info("crontab","del_img_mv_to_new","{$row['url']}:复制至新key:$newfile;失败;{$aws_copy['message']}");
    }
    //更新img库



    DB::TransBegin();
    $rs = DB::Update("public.img",$row['url'],array("url"=>$newfile),"url");


    if(!$rs){
        QLog::info("crontab","del_img_mv_to_new","{$row['url']}:更新img表url为{$newfile}:失败");
        DB::TransRollback();
        continue;
    }
    QLog::info("crontab","del_img_mv_to_new","{$row['url']}:更新img表url为{$newfile}:成功");
    $rs2 = DB::Update("public.img_del_record",$row['id'],array("ceph_del"=>"t"),"id");
    if(!$rs2){
        QLog::info("crontab","del_img_mv_to_new","{$row['url']}:更新img_del_record表，ceph_del=t:失败");
        DB::TransRollback();
        continue;
    }
    QLog::info("crontab","del_img_mv_to_new","{$row['url']}:更新img_del_record表，ceph_del=t:成功");

    //设置删除原始文件 ,原始文件删除成功失败，均不判断，此处设计锁表等操作，所以只记录下来。如果删除失败需要手动删除

    $return_var = Storage::del($row['url'],300);

    if ($return_var) {
        $result = "执行shell失败";
        $message = "img_del_record表,id={$row['id']};url={$row['url']};new url:{$newfile};".json_encode($return_var,JSON_UNESCAPED_UNICODE);
        QNotify::apiError("删除原始文件失败",$message);
        DB::TransRollback();
    } else {
        $result = "执行shell成功";
        DB::TransCommit();
    }
    QLog::info("crontab","del_img_mv_to_new","{$row['url']}:删除原始文件;result:{$result};output:".json_encode($return_var,JSON_UNESCAPED_UNICODE));

}
QLog::info("crontab","del_img_mv_to_new","end");