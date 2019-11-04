<?php

/**
 * 图片积分相关
 * Class QImgPoints
 */

class QImgPoints {
    
    public static $log_prefer = 'img_operate';
    public static $log_action = 'points';
    public static $old_img_time_end = '2019-05-11 00:00:00';//原始数据处理的截止时间
    
    //定义积分配置数据
    public static $pointConf = [
        'old_data' => 2,//原始数据
        'praise' => 1,//点赞
        'favorite' => 1,//收藏
        'download' => 5,//下载
        'upload_task' => 5,//上传任务
        'city_img_task' => 5,//上传任务
        
        //普通图片
        'img_reject' => -2,//图片被驳回
        'img_pass' => 2,//上传图片并通过审核
        'img_delete' => -2,//图片被删除（删除所有相关积分）
        'img_edit' => -2,//图片被编辑
        
        //积分商城上线前图片积分--第一次脚本处理
        'old_data_reject' => -10,//图片被驳回
        'old_data_pass' => 10,//上传图片并通过审核
        'old_data_delete' => -10,//图片被删除（删除所有相关积分）
        'old_data_edit' => -10,//图片被编辑
        
        //积分商城上线前图片积分--后期使用--5倍积分
        'old_img_reject' => -10,//图片被驳回
        'old_img_pass' => 10,//上传图片并通过审核
        'old_img_delete' => -10,//图片被删除（删除所有相关积分）
        'old_img_edit' => -10,//图片被编辑
        
        //任务城市
        'img_city_reject' => -5,//图片被驳回
        'img_city_pass' => 5,//上传图片并通过审核
        'img_city_delete' => -5,//图片被删除（删除所有相关积分）
        'img_city_edit' => -5,//图片被编辑
        
        
        //2019-03-05鑫妹子修改 不再区分首图
        'city_first_pass' => 50,//城市首图--全部
        'city_first_reject' => -50,//城市首图--全部
        'city_first_delete' => -50,//城市首图--全部（删除所有相关积分）
        'city_first_edit' => -50,//城市首图--全部
        'city_myfirst_pass' => 5,//城市首图--我的
        'city_myfirst_reject' => -5,//城市首图--我的
        'city_myfirst_delete' => -5,//城市首图--我的（删除所有相关积分）
        'city_myfirst_edit' => -5,//城市首图--我的
        
        //2019-05-23增加精选推荐积分
        'recommend' => 20,//精选推荐
        'unrecommend' => -20,//取消精选推荐
    ];
    
    /*
     * 操作范围：图片被审核驳回|编辑
     */
    public static function editPointDeal( $params = [] ){
        
        //查询当前是否有未处理的审核通过的图片积分
        $pending_rs = self::getPendingPass($params);
        if( $pending_rs ){
            return false;
        }
        
        $img_id = $params['img_id'];//图片id
        $operate_username = $params['operate_username'];//操作用户名
        $username = $params['username'];//积分用户名
        $operate_type = $params['operate_type'];//操作类型
        
        //计算图片积分:根据之前已有的图片积分记录
        $change_points = self::getEditPoints(['img_id'=>$img_id]);
        if( $change_points == 0 ){
            return true;
        }
        
        $params_insert = [
            'operate_username' => $operate_username,
            'img_id' => $img_id,
            'operate_source' => 'img_'.$operate_type,
            'username' => $username,
            'change_points' => $change_points,
        ];

        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分统计信息如下：". var_export($params_insert, true));
        $insert_rs = self::pointInsert($params_insert);
        if( !$insert_rs ){
            return false;
        }
        
        return true;
    }
    
    /*
     * 查询当前图片id是否有未处理完成【积分计算】的图片审核通过的记录
     */
    public static function getPendingPass($params=[]){
        $record_sql = " SELECT * FROM ".QImgSearch::$auditRecordsTableName
                ." WHERE point_state = 'pending' AND img_id = '{$params['img_id']}' ORDER BY id ASC LIMIT 1 ";
        $db_rs = DB::GetQueryResult($record_sql);
        return $db_rs;
    }
    
    /*
     * 查询当前图片eid是否有未处理完成【积分计算】的图片审核通过的记录
     */
    public static function getPendingPassList($params=[]){
        $eidStr = array2insql($params['eid']);

        $record_sql = " SELECT ar.* FROM ".QImgSearch::$auditRecordsTableName ." as ar join ".QImgSearch::$imgTableName ." as i on ar.img_id = i.id "
                ."  WHERE ar.point_state = 'pending' AND i.eid IN ($eidStr) ORDER BY ar.id ASC LIMIT 1 ";
        $db_rs = DB::GetQueryResult($record_sql);
        return $db_rs;
    }
    
    /*
     * 操作范围：图片被删除积分计算
     */
    public static function deletePointDeal( $params = [] ){
        
        //查询当前是否有未处理的审核通过的图片积分
        $pending_rs = self::getPendingPass($params);
        if( $pending_rs ){
            return false;
        }
        
        $img_id = $params['img_id'];//图片id
        $operate_username = $params['operate_username'];//操作用户名
        $username = $params['username'];//积分用户名
        $operate_type = $params['operate_type'];//操作类型
        
        //删除要计算所有相关积分
        $change_points = self::getDeletePoints(['img_id'=>$img_id]);
        if( $change_points == 0 ){
            return true;
        }
        
        //更新积分
        $params_insert = [
            'operate_username' => $operate_username,
            'img_id' => $img_id,
            'operate_source' => 'img_delete',
            'username' => $username,
            'change_points' => $change_points,
        ];
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分统计信息如下：". var_export($params_insert, true));
        $insert_rs = self::pointInsert($params_insert);
        return $insert_rs ? true : false;
    }
    
    /*
     * 判断图片第一次提交审核的时间是否在之前
     * 指定时间 2019-03-31即2019-04-01
     */
    public static function getImgFirstSubmitInfo($params=[]){
        $img_id = $params['img_id'];//图片id
        
        $first_time_end = self::$old_img_time_end;
        $operate_type = 'submit';
        
        $sql = " SELECT * FROM ". QImgSearch::$auditRecordsTableName
                ." WHERE img_id = '{$img_id}' 
                    AND operate_type = '{$operate_type}' 
                        ORDER BY id ASC LIMIT 1 ";
        $records_rs = DB::GetQueryResult($sql);
        if( !$records_rs ){
            //失败，报错
            return [
                'status' => 1021,
                'message' => '数据有误',
            ];
        }
        
        //有，判断时间
        if( $records_rs['create_time'] < $first_time_end ){
            //双倍积分
            return [
                'status' => 0,
                'data' => [
                    'is_double_points' => true,
                ],
            ];
        }
        
        //普通积分
        return [
            'status' => 0,
            'data' => [
                'is_double_points' => false,
            ],
        ];
        
    }
    
    /*
     * 获取积分操作来源--operate_source
     * 和积分数量
     */
    public static function getOperateSource( $params = [] ){
        
        $img_id = $params['img_id'];//图片id
        $username = $params['username'];//积分用户名
        $operate_type = $params['operate_type'];//操作类型
        
        /*
         * 获取用户之前的积分记录，获取对应分数
         * --如果用户之前有 图片被审核通过的积分记录，则用以前的积分处理
         * --如果没有，证明是第一次，通过城市获取积分和状态
         */
        $points_sql = " SELECT operate_source, change_points FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE operate_info->'img_id' = '{$img_id}' 
                    AND operate_source LIKE '%pass' 
                    ORDER BY id ASC 
                    LIMIT 1";
        $points_rs = DB::GetQueryResult($points_sql);
        
        //没有通过审核过--没有审核通过后的积分，不处理
        if( !$points_rs && $operate_type != 'pass' ){
            $log_msg = "用户{$username}积分更新信息：图片id={$img_id}之前没有被审核通过过，本次操作不扣分，sql如下：". $points_sql;
            QLog::info(self::$log_prefer,self::$log_action, $log_msg);
            return [
                'status' => 1020,
                'message' => '图片没有被审核通过加分的记录，本次不扣分',
                'data' => [
                    'change_points' => 0,
                    'operate_source' => '',
                ],
            ];
        }
        
        /*
         * 判断：如果是之前有审核通过记录的，积分变化和上次一样
         * 如果没有，是第一次，审核通过，用配置数据
         */
        
        if( $points_rs ){
            
            //去掉后面的pass、只留前面的
            $operate_source_rs = explode("_", $points_rs['operate_source']);
            array_pop($operate_source_rs);
            $operate_source_tmp2 = implode("_", $operate_source_rs);
            //原始数据二次处理和第一次脚本处理的标记区分
            if( $operate_source_tmp2 == 'old_data' ){
                $operate_source_tmp2 = 'old_img';
            }
            $operate_source = $operate_source_tmp2."_".$operate_type;
            
            //设置积分：如果是审核通过加分，其他减分
            if( $operate_type == 'pass' ){
                $change_points = $points_rs['change_points'];
                //2019-03-18只有第一次pass审核通过会增加用户的总积分，多次审核通过不重复加分
                $total_points_change = 'no';
            }else{
                $change_points = 0 - $points_rs['change_points'];
            }
            
            
        }else{
            
            //判断是否需要双倍积分
            $old_img_double_rs = self::getImgFirstSubmitInfo($params);
            if( $old_img_double_rs['status'] != 0 ){
                QLog::info(self::$log_prefer,self::$log_action, $log_msg);
                return [
                    'status' => 1022,
                    'message' => '操作失败，请重试',
                    'data' => [
                        'change_points' => 0,
                        'operate_source' => '',
                    ],
                ];
            }
            
            if( $old_img_double_rs['data']['is_double_points'] ){
                $operate_source = "old_img_".$operate_type;
            }else{
                $operate_source = "img_".$operate_type;
            }
            
            $change_points = self::$pointConf[$operate_source];
        }
        
        return [
            'status' => 0,
            'message' => '操作成功',
            'data' => [
                'operate_source' => $operate_source,
                'total_points_change' => $total_points_change ? $total_points_change : 'yes' ,
                'change_points' => $change_points ? $change_points : 0,
            ],
        ];
        
    }
    
    /*
     * 图片被删除--需要更新用户所有该图片的相关积分，包括点赞、下载、收藏和图片积分
     * 获取需要减除的积分
     */
    public static function getDeletePoints( $params = [] ){
        
        $img_id = $params['img_id'];//图片id
        
        //查询该图片相关的所有积分--包含积分商城上线前的积分
        $points_sql = " SELECT SUM(change_points) AS change_points FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE operate_info->'img_id' = '{$img_id}' AND operate_source NOT IN ('exchange', 'old_data_all') ";
        $points_rs = DB::GetQueryResult($points_sql);
        
        $change_points = 0 - $points_rs['change_points'];
        
        return $change_points;
        
    }
    
    /*
     * 图片被编辑或驳回--需要更新用户所有该图片的相关积分，但不包括点赞、下载、收藏
     * 获取需要减除的积分
     */
    public static function getEditPoints( $params = [] ){
        
        $img_id = $params['img_id'];//图片id
        
        //查询该图片相关的所有积分--包含积分商城上线前的积分

        $operate_source_arr = [
            'img_pass',
            'img_edit',
            'img_reject',
            'old_img_edit',
            'old_img_delete',
            'old_img_pass',
            'old_img_reject',
            'old_data_pass',
            'activity_img_pass',
            'recommend',
            'unrecommend',
        ];
        
        $operate_source_str = implode("','", $operate_source_arr);
        
        $points_sql = " SELECT SUM(change_points) AS change_points FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE operate_info->'img_id' = '{$img_id}' 
                    AND operate_source IN ('{$operate_source_str}') ";
        $points_rs = DB::GetQueryResult($points_sql);
        
        $change_points = 0 - $points_rs['change_points'];
        
        return $change_points;
        
    }
    
    /*
     * 图片被下载、点赞、收藏
     */
    public static function praisePointDeal( $params = [] ){
        
        $operate_username = $params['operate_username'];
        $username = $params['username'];//积分用户名
        $operate_source = $params['operate_type'];
        $img_id = $params['img_id'];
        
        //当日被下载计分：超过5次不再计入
        $today_begin_time = date("Y-m-d 00:00:00");
        $today_download_points_sql = " SELECT COUNT(1) AS count FROM "
                .QImgSearch::$pointsTraceTableName
                ." WHERE username = '{$username}' AND create_time >= '{$today_begin_time}' ";
                
        //下载五次，点赞和收藏五次
        if( $operate_source == 'download' ){
            $today_download_points_sql.= " AND operate_source = 'download' ";
        }else{
            $today_download_points_sql.= " AND ( operate_source = 'favorite' OR operate_source = 'praise' ) ";
        }
                
        $today_download_points_rs = DB::GetQueryResult($today_download_points_sql);
        if( $today_download_points_rs['count'] >= 5 ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：当日超过5次，不积分，sql如下："
                .$today_download_points_sql);
            return true;//不需要计分
        }
        
        /*
         * 验证用户身份:
         * 1. 自己的图片下载不计分
         * 2. 用户多次下载不计分
         */
        
        if( $operate_username == $username ){//自己的图片不计分
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：操作用户操作自己的图片，不积分");
            return true;
        }
        
        //用户多次下载不计分
        $user_download_sql = " SELECT id FROM "
                .QImgSearch::$pointsTraceTableName
                ." WHERE operate_info IS NOT NULL AND operate_info->'img_id' = '{$img_id}' 
                    AND operate_source = '{$operate_source}' AND operate_username = '{$operate_username}' ";
        $user_download_rs = DB::GetQueryResult($user_download_sql);
        if( $user_download_rs ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：该用户多次进行同一操作，不重复积分：".$operate_username);
            return true;//不需要计分
        }
        
        //积分流水表
        $params_insert = [
            'username' => $username,
            'change_points' => self::$pointConf[$operate_source],
            'operate_source' => $operate_source,
            'operate_username' => $operate_username,
            'img_id' => $img_id,
        ];
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：积分统计信息如下：". var_export($params_insert, true));
        $insert_rs = self::pointInsert($params_insert);
        
        return $insert_rs ? true : false;
    }
    
    /*
     * 图片被下载--单独处理
     */
    public static function downloadPointDeal( $params = [] ){
        
        $operate_username = $params['operate_username'];
        $username = $params['username'];//积分用户名
        $operate_source = $params['operate_type'];
        $img_id = $params['img_id'];
        
        /*
         * 验证用户身份:
         * 1. 自己的图片下载不计分
         * 2. 用户多次下载不计分
         */
        
        if( $operate_username == $username ){//自己的图片不计分
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：操作用户操作自己的图片，不积分");
            return true;
        }
        
        //用户多次下载不计分
        $user_download_sql = " SELECT id FROM "
                .QImgSearch::$pointsTraceTableName
                ." WHERE operate_info IS NOT NULL AND operate_info->'img_id' = '{$img_id}' 
                    AND operate_source = '{$operate_source}' AND operate_username = '{$operate_username}' ";
        $user_download_rs = DB::GetQueryResult($user_download_sql);
        if( $user_download_rs ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：该用户多次进行同一操作，不重复积分：".$operate_username);
            return true;//不需要计分
        }
        
        //积分流水表
        $params_insert = [
            'username' => $username,
            'change_points' => self::$pointConf[$operate_source],
            'operate_source' => $operate_source,
            'operate_username' => $operate_username,
            'img_id' => $img_id,
        ];
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分处理（图片id={$img_id}积分处理）：积分统计信息如下：". var_export($params_insert, true));
        $insert_rs = self::pointInsert($params_insert);
        
        return $insert_rs ? true : false;
    }
    
    /*
     * 更新任务积分
     */
    public static function taskPointDeal( $params = [] ){
        
        //任务分
        $params = [
            'username' => $params['username'],
            'operate_username' => $params['username'],
            'task_id' => $params['task_id'],
            'img_id' => $params['img_id'] ? $params['img_id'] : '',
            'city_name' => $params['city_name'] ? $params['city_name'] : '',
            'operate_source' => $params['operate_source'],
            'change_points' => self::$pointConf[$params['operate_source']],
        ];
        
        $insert_rs = self::pointInsert($params);
        return $insert_rs ? true : false;
    }
    
    /*
     * 计分操作计算
     */
    public static function pointInsert( $params = [] ){
        $total_points_change = $params['total_points_change'];
        $username = $params['username'];
        $img_id = $params['img_id'];
        $city_name = $params['city_name'];
        $change_points = $params['change_points'];
        $product_eid = $params['product_eid'];
        $activity_id = $params['activity_id'] ? $params['activity_id'] : 0;
        $is_first_pass = $params['is_first_pass'] ? $params['is_first_pass'] : 'f';
        
        //查询图片之前的积分汇总--查上次的记录即可
        $point_last_trace_sql = " SELECT current_points + {$change_points} AS old_points FROM "
                . QImgSearch::$userTableName 
                ." WHERE username = '{$username}' LIMIT 1 ";

        //事务操作--外部有事务，内部不单独处理事务
        //积分流水表
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分更新信息如下：开始更新");
        
        //json数据记录
        $operate_info_arr = [];
        if( $img_id ){
            $operate_info_arr['img_id'] = (int)$img_id;//图片id
        }
        if( $city_name ){
            $operate_info_arr['city_name'] = $city_name;//城市名称
        }
        if( $product_eid ){
            $operate_info_arr['product_eid'] = $product_eid;//商品eid
        }
        if( $activity_id ){
            $operate_info_arr['activity_id'] = $activity_id;//任务id
        }
        
        $operate_info = json_encode($operate_info_arr,JSON_UNESCAPED_UNICODE);
        $insert_sql = " INSERT INTO ". QImgSearch::$pointsTraceTableName." ( 
            username
            , operate_username
            , old_points
            , change_points
            , operate_source
            , operate_info
            , create_time
            , activity_id
            , is_first_pass
            ) VALUES ( 
            '{$username}'
            , '{$params['operate_username']}'
            , ({$point_last_trace_sql})
            , {$change_points}
            , '{$params['operate_source']}'
            , '{$operate_info}'
            , now()
            , '{$activity_id}'
            , '{$is_first_pass}'
            ) RETURNING id ";
        
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：". $insert_sql);
        $insert_rs = DB::Query($insert_sql);
        $insert_id = pg_fetch_row($insert_rs);
        
        if( !$insert_id ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：积分插入失败");
            return false;
        }
        
        //更新用户表积分数据
        $user_points_update_sql = " UPDATE ". QImgSearch::$userTableName 
            ." SET total_points = CASE WHEN ({$change_points} > 0 AND '{$total_points_change}' != 'no') THEN total_points + {$change_points} ELSE  total_points END 
            , current_points = current_points + {$change_points}
            , last_date_points = CASE WHEN last_point_date = current_date THEN last_date_points + {$change_points}
                ELSE {$change_points} END 
            , last_point_date = current_date
             WHERE username = '{$username}' RETURNING id ";
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}用户积分更新sql如下：". $user_points_update_sql);
        $update_rs = DB::Query($user_points_update_sql);
        $update_id = pg_fetch_row($update_rs);
        if( !$update_id ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：用户表积分汇总信息更新失败");
            return false;
        }
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：用户积分更新成功");
        return true;
    }
    
    /*
     * 积分处理
     */
    public static function pointsDeal($params=[]){
        
        /*
         * 用户编辑图片信息
         */
        if( $params['operate_type'] == 'edit' ){
            //如果是从审核通过--编辑
            if( $params['audit_state'] == 2 ){
                return self::editPointDeal($params);
            }else{
                return true;
            }
        }
        
        /*
         * 图片被审核驳回
         */
        if( $params['operate_type'] == 'reject' ){
            return self::editPointDeal($params);
        }
        
        /*
         * 图片被删除
         */
        if( $params['operate_type'] == 'delete' ){
            return self::deletePointDeal($params);
        }
        
        /*
         * 图片被下载
         */
        if( $params['operate_type'] == 'download' ){
            return self::downloadPointDeal($params);
        }
        
        /*
         * 图片被收藏、点赞
         */
        if( in_array($params['operate_type'], ['favorite','praise']) ){
            return self::praisePointDeal($params);
        }
    }
    
}
