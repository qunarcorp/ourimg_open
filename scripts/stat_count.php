<?php

/**
* 每日统计下载量。收藏点赞。浏览量 给用户发送消息
 01 10 * * *
 */

require_once __DIR__."/../htdocs/app_api.php";

crontab_run_one("crontab","stat_count_mark");
QLog::info("crontab","stat_count","bgn");

$yeserday = date("Y-m-d",strtotime("-1 day"));
$today = date("Y-m-d");

$sql = "
WITH
b AS ( SELECT COUNT ( 1 ) AS b, img_id FROM PUBLIC.browse_trace WHERE create_time BETWEEN '{$yeserday}' AND '{$today}' GROUP BY img_id )
,d AS ( SELECT COUNT ( 1 ) AS d, img_id FROM PUBLIC.download_history WHERE update_time BETWEEN '{$yeserday}' AND '{$today}' GROUP BY img_id )
,f AS ( SELECT COUNT ( 1 ) AS f, img_id FROM PUBLIC.favorite WHERE favorite_type='img' and create_time BETWEEN '{$yeserday}' AND '{$today}' GROUP BY img_id )
,pr AS ( SELECT COUNT ( 1 ) AS p, img_id FROM PUBLIC.praise WHERE create_time BETWEEN '{$yeserday}' AND '{$today}' GROUP BY img_id )
,base AS ( 
	SELECT img_id FROM b 
	UNION SELECT img_id FROM d 
	UNION SELECT img_id FROM f 
	UNION SELECT img_id FROM pr
) 

SELECT
	img.username,array_to_json(array_agg(distinct img.title)) as title,
	coalesce(SUM ( b.b ),0) AS b,
	coalesce(SUM ( d.d ),0) AS d,
	coalesce(SUM ( f.f ),0) AS f,
	coalesce(SUM ( pr.p ),0) AS p
FROM
	base
	JOIN PUBLIC.img ON base.img_id = img.id and img.audit_state=2
	LEFT JOIN b ON base.img_id = b.img_id
	LEFT JOIN d ON base.img_id = d.img_id
	LEFT JOIN f ON base.img_id = f.img_id
	LEFT JOIN pr ON base.img_id = pr.img_id 
GROUP BY img.username
";
QLog::info("crontab","stat_count",$sql);
$result = DBSLAVE::Query($sql,false);

QLog::info("crontab","stat_count","sql result :".($result?"true":"false"));

while($row = pg_fetch_assoc($result)){
    $titles = json_decode($row['title'],true);


    $message_title = implode("、",array_slice($titles,0,2));
    if(count($titles)>2){
        $message_title .= "等";
    }

    $count = array();
    if($row['b']){
        $count[] = "{$row['b']}位用户浏览";
    }
    if($row['p']){
        $count[] = "获得了{$row['p']}个点赞";
    }
    if($row['f']){
        $count[] = "被{$row['f']}位用户收藏";
    }
    if($row['d']){
        $count[] = "{$row['d']}位用户下载";
    }

    $message = "您贡献的素材{$message_title}素材，昨天共有".implode("，",$count);

    $rs = QImgMessage::addPraiseMessage($row['username'],$message);

    QLog::info("crontab","stat_count","username:{$row['username']};message:{$message};rs:".($rs?"true":"false"));
}

QLog::info("crontab","stat_count","end");