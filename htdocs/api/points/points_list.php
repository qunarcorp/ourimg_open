<?php

/**
 * 积分列表明细接口
 */

require dirname(dirname(dirname(__FILE__))) . '/app_api.php';
session_write_close();//关闭session

//验证用户登录
QImgPersonal::checkUserLoginNew();

$offset = filter_input(INPUT_GET, 'offset', FILTER_SANITIZE_NUMBER_INT);
$limit = filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);

//时间选择器
$time_select = filter_input(INPUT_GET, 'time_select', FILTER_SANITIZE_STRING);
//积分类型:收入|支出
$point_type = filter_input(INPUT_GET, 'point_type', FILTER_SANITIZE_STRING);

$params = [
    'limit' => $limit,
    'offset' => $offset,
    'time_select' => $time_select,
    'point_type' => $point_type,
];
$point_rs = QPointInfo::getUserPointsTrace($params);

$point_arr = [];

$point_rs_arr = $point_rs['point_list'];
$img_title_arr = $point_rs['img_title'];
$activity_title_arr = $point_rs['activity_title'];


$product_title = $point_rs['product_title'];

$usernameList = array_filter(array_unique(array_column($point_rs["point_list"], "operate_username")), function($item){
    return ! empty($item);
});
$usernameAndAvatar = empty($usernameList) ? [] : array_replace_key(QAuth::userAvatar($usernameList), "username");

foreach( $point_rs_arr as $k => $v ){
    

    
    $operate_info = json_decode($v['operate_info'], true);
    
    
    //积分来源设置
    $point_desc = '';
    
    if( $v['operate_source'] == 'activity_img_pass' ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」获活动任务「".$activity_title_arr[$v['activity_id']]."」奖励上传积分";
    }elseif(strstr($v['operate_source'], 'pass')){
        
        //查询图片之前是否有积分记录
        $exist_point_trace = false;
        if( $v['is_first_pass'] != 't' ){
            $points_pre_sql = " SELECT * FROM "
                    . QImgSearch::$pointsTraceTableName ." WHERE username = '{$login_user_name}' 
                     AND operate_info->'img_id' = '{$v['img_id']}' 
                         AND operate_source IN ('{$v['operate_source']}','old_data_pass') 
                          AND id < '{$v['id']}' ORDER BY id ASC LIMIT 1 ";

            $points_pre_rs = DB::GetQueryResult($points_pre_sql);
            if( $points_pre_rs ){
                $exist_point_trace = true;
            }
        }
        
        if( !$exist_point_trace ){
            $point_desc = "上传图片“「".$img_title_arr[$v['img_id']]."」“并审核通过 ";
        }else{
            $point_desc = "图片“「".$img_title_arr[$v['img_id']]."」“ 复审通过（图片获取过的上传奖励积分恢复）";
        }
        
        //双倍积分
        if( $v['operate_source'] == 'old_img_pass' ){
            $point_desc.= "，活动10倍积分奖励";
        }
    }elseif( strstr($v['operate_source'], 'delete') ){
        $point_desc = "图片“「".$img_title_arr[$v['img_id']]."」“ 被删除（该图片获取过的所有奖励积分均被扣减）";
        if( $v['change_points'] == 0 ){
            $v['change_points'] = '-'.$v['change_points'];
        }
    }elseif( strstr($v['operate_source'], 'reject') ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被驳回";
    }elseif( strstr($v['operate_source'], 'edit') ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被修改待通过（该图片获取过的上传奖励积分被扣减）";
    }elseif( $v['operate_source'] == 'upload_task' ){
        $point_desc = "完成任务【上传任务】";
    }elseif( $v['operate_source'] == 'download' ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被下载（下载用户：".$usernameAndAvatar[$v['operate_username']]["name"]."）";
    }elseif( $v['operate_source'] == 'favorite' ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被收藏（收藏用户：".$usernameAndAvatar[$v['operate_username']]["name"]."）";
    }elseif( $v['operate_source'] == 'praise' ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被点赞（点赞用户：".$usernameAndAvatar[$v['operate_username']]["name"]."）";
    }elseif( $v['operate_source'] == 'city_img_task' ){
        $point_desc = "上传城市—".$operate_info['city_name']."首图“「".$img_title_arr[$v['img_id']]."」“";
    }elseif( $v['operate_source'] == 'exchange' ){
        $point_desc = "兑换【".$product_title[$v['product_eid']]."】";
    }elseif( $v['operate_source'] == 'old_data_all' ){
        $point_desc = "积分商城上线前图片上传积分10倍奖励";
    }elseif( $v['operate_source'] == 'activity_task' ){
        $point_desc = "活动任务「".$activity_title_arr[$v['activity_id']]."」奖励任务积分";
    }elseif( $v['operate_source'] == 'recommend' ){//精选推荐
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被选中“精选推荐”";
    }elseif( $v['operate_source'] == 'unrecommend' ){
        $point_desc = "图片「".$img_title_arr[$v['img_id']]."」 被取消“精选推荐”";
    }
    
    if( $v['change_points'] > 0 ){
        $v['change_points'] = '+'.$v['change_points'];
    }
    
    $point_arr[] = [
        'point_date' => date("Y-m-d",strtotime($v['create_time'])),
        'old_points' => $v['old_points'],
        'change_points' => $v['change_points'],
        'point_desc' => $point_desc,//积分来源描述
        'exist_point_trace' => $exist_point_trace,//是否有积分奖励
        'operate_source' => $v['operate_source'],//操作类型
        'city_name' => $operate_info['city_name'],//城市名称
        'product_eid' => $operate_info['product_eid'],//产品eid
        'product_num' => $operate_info['product_num'] ? $operate_info['product_num'] : '1',//产品兑换数量
        'product_title' => $product_title[$v['product_eid']],//产品标题
        'img_title' => $img_title_arr[$v['img_id']],//图片标题
        'activity_title' => $activity_title_arr[$v['activity_id']],//活动标题
        'activity_id' => $v['activity_id'],//活动id
    ];
}


$rs = [
    "status" => 0,
    "message" => "查询成功",
    "point_arr" => $point_arr,
    "points_count" => QPointInfo::getUserPointsCount($params),
];
display_json_str_common($rs, $callback);
