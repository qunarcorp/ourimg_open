<?php

/**
 * 活动积分计算类
 */

class QActivity_TaskPoint {
    
    //判断是否是第一次提交审核-只有第一次提交计算任务积分
    public static function isFirstSubmit($params=[]){
        $audit_recode_sql = " SELECT * FROM ". QImgSearch::$auditRecordsTableName 
                ." WHERE img_id = '{$params['img_id']}' AND operate_type = 'submit' AND id < '{$params['id']}' LIMIT 1 ";
        $audit_recode_rs = DB::GetQueryResult($audit_recode_sql);
        if( !$audit_recode_rs ){
            return true;
        }else{
            return false;
        }
    }
    

    /*
     * 查询list:只获取有效的
     * state状态 在线
     * 时间范围内
     */
    public static function getValidActivities($params=[]){
        $city_id = $params['city_id'];//只有一个
        $theme_keywords = $params['theme_keywords'];//array
        
        if( !$city_id && !$theme_keywords ){
            return false;
        }
        
        //查询结果
        $sql = " SELECT * FROM ".QImgSearch::$activityTasksTableName
                ." WHERE state = 'online' AND task_points > 0 AND need_img_count > 0 
                AND ( begin_time IS NULL OR begin_time <= now() )
                AND ( end_time IS NULL OR end_time >= now() )";
        
        if( $city_id ){
            $citySight = QSight::sight_info($city_id);

            $cityIds = empty($citySight) ? [$city_id] : explode(" ", $citySight["parents"]);
            $city_sight = [
                " 'city_sight' != ALL(activity_type) "
            ];

            foreach ($cityIds as $cityId) {
                $city_sight[] = " city_sights->>'{$cityId}' IS NOT NULL ";
            }

            $sql .= sprintf(" AND ( %s ) ", implode(" OR ", $city_sight));
        }
        if( $theme_keywords ){
            $theme_keywords_str = implode("','", $theme_keywords);
            $sql .= " AND ( 'theme' != ALL(activity_type) OR ( theme_keywords::text[] && ARRAY['".$theme_keywords_str."'] ) ) ";
        }

        $list = DB::GetQueryResult($sql, false);
        
        //返回
        return $list;
    }
    
    /*
     * 查询list:只获取有效的
     * state状态 在线
     * 时间范围内
     */
    public static function getValidActivitiesImgpoints($params=[]){
        $city_id = $params['city_id'];//只有一个
        $theme_keywords = $params['theme_keywords'];//array
        
        if( !$city_id && !$theme_keywords ){
            return false;
        }
        
        //查询结果
        $sql = " SELECT * FROM ".QImgSearch::$activityTasksTableName
                ." WHERE state = 'online' AND img_upload_points > 0 
                AND ( begin_time IS NULL OR begin_time <= now() )
                AND ( end_time IS NULL OR end_time >= now() )";
        
        if( $city_id ){
            $citySight = QSight::sight_info($city_id);

            $cityIds = empty($citySight) ? [$city_id] : explode(" ", $citySight["parents"]);
            $city_sight = [
                " 'city_sight' != ALL(activity_type) "
            ];

            foreach ($cityIds as $cityId) {
                $city_sight[] = " city_sights->>'{$cityId}' IS NOT NULL ";
            }

            $sql .= sprintf(" AND ( %s ) ", implode(" OR ", $city_sight));
        }
        if( $theme_keywords ){
            $theme_keywords_str = implode("','", $theme_keywords);
            $sql .= " AND ( 'theme' != ALL(activity_type) OR ( theme_keywords::text[] && ARRAY['".$theme_keywords_str."'] ) ) ";
        }
        
        $list = DB::GetQueryResult($sql, false);
        
        //返回
        return $list;
    }
    
    /*
     * 查询list:根据id查询
     */
    public static function getActivities($params=[]){
        $id = $params['id'];//array
        
        if( !$id ){
            return false;
        }
        
        //查询结果
        $ids = implode(',', $id);
        $sql = " SELECT * FROM ".QImgSearch::$activityTasksTableName
                ." WHERE id IN ({$ids}) ";
        
        $list = DB::GetQueryResult($sql, false);
        
        //返回
        return $list;
    }
    
    //没有任务记录，插入
    public static function addTask($params=[]){
        $activity_info = $params['activity_info'];
        $audit_record = $params['audit_record'];
        $insert_info = [
            'task_name' => 'activity',//目前都是上传任务，主要为了跟前端兼容
            'activity_id' => $activity_info['id'],
            'username' => $audit_record['username'],
            'complete_num' => 1,
            'complete_state' => $activity_info['need_img_count'] == 1 ? 'done' : 'doing',
            'create_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
            'img_id' => "{".$audit_record['img_id']."}",
        ];
        $db_rs = DB::Insert(QImgSearch::$dailyTaskTableName, $insert_info, 'complete_state');
        $complete_state = $db_rs;
        return $complete_state;
    }
    
    //有任务记录，没完成-更新
    public static function updateTask($params=[]){
        
        $activity_record_rs = $params['activity_record_rs'];
        $activity_info = $params['activity_info'];
        $audit_record = $params['audit_record'];
        
        $current_img_id = json_decode($activity_record_rs['img_id_arr'], true);
        $current_img_id[] = $audit_record['img_id'];
        $img_id_arr = "{".implode(",",$current_img_id)."}";

        $complete_state_num = $activity_info['need_img_count'] - 1;
        $update_sql = "UPDATE ". QImgSearch::$dailyTaskTableName
                ." SET complete_num = complete_num + 1 
                  , complete_state = CASE WHEN complete_num >= {$complete_state_num} THEN 'done' ELSE 'doing' END 
                  , update_time = now() 
                  , img_id = '{$img_id_arr}' 
                  WHERE id = '{$activity_record_rs['id']}' 
                      RETURNING complete_state ";
                  
        $db_rs = DB::Query($update_sql);
        //读取update返回信息
        $update_info = pg_fetch_row($db_rs);
        $complete_state = $update_info[0];
        return $complete_state;
    }
    
    //更新状态
    public static function errorUpdate($params=[]){
        $update = [
            'point_state' => $params['point_state'],
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $update_rs = DB::Update(QImgSearch::$auditRecordsTableName, $params['id'], $update, 'id');
        return $update_rs ? $update_rs : false;
    }
    
    //获取用户当前的任务积分记录
    public static function getCurrentActivities($params=[], $username){
                
        //查看当前用户是否有该任务完成记录
        $monday_begin = date('Y-m-d', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600));
        $activity_record_sql = " SELECT *, array_to_json(img_id) AS img_id_arr FROM ".QImgSearch::$dailyTaskTableName
                ." WHERE activity_id = '{$params['id']}' AND username = '{$username}' 
                 AND create_time >= 
                 (CASE WHEN '{$params['points_cycle']}' = 'weekly' THEN '{$monday_begin}' 
                     WHEN '{$params['points_cycle']}' = 'daily' THEN current_date
                     ELSE '1970-01-01' END  )
                    ORDER BY id DESC LIMIT 1 ";
        $activity_record_rs = DB::GetQueryResult($activity_record_sql);
        
        return $activity_record_rs ? $activity_record_rs : [];
    }
    
    //更新活动表中记录图片的数量
    public static function addImgCount($params=[]){
        //更新活动表中图片数量
        $update_activity_sql = " UPDATE ".QImgSearch::$activityTasksTableName
                ." SET now_img_counts = now_img_counts + 1 WHERE id = '{$params['id']}' ";
        $activity_update_rs = DB::Query($update_activity_sql);
        return $activity_update_rs ? $activity_update_rs : false;
    }
    
    //已完成任务，需要增加任务积分
    public static function pointChange($params=[]){
        $complete_state = $params['complete_state'];
        $audit_record = $params['audit_record'];
        $activity_info = $params['activity_info'];
        if( $complete_state == 'done' ){
            //更新积分
            $params_insert = [
                'operate_username' => $audit_record['username'],
                'operate_source' => 'activity_task',
                'username' => $audit_record['username'],
                'change_points' => $activity_info['task_points'],
                'activity_id' => $activity_info['id'],
                'total_points_change' => 'yes',
            ];

            $insert_rs = QImgPoints::pointInsert($params_insert);
            return $insert_rs ? true : false;
        }else{
            return true;
        }
        
    }
    
    //插入活动和图片对应表
    public static function insertRelation($params=[]){
        $relation_info = [
            'activity_id' => $params['activity_id'],
            'img_id' => $params['img_id'],
        ];
        $relation_rs = DB::Insert(QImgSearch::$activityImgRelationTableName, $relation_info, 'id');
        return $relation_rs ? $relation_rs : false;
    }
    
    //更新该条记录积分统计状态
    public static function updateAuditRecord($params=[]){
        $record_id = $params['record_id'];
        $audit_record_update = [
            'point_state' => 'done',
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $audit_record_update_rs = DB::Update(QImgSearch::$auditRecordsTableName, $record_id, $audit_record_update, 'id');
        return $audit_record_update_rs ? $audit_record_update_rs : false;
    }
    
    /*
     * 查询用户第一次审核通过所有的图片积分
     */
    public static function getFirstPassList($params=[]){
        
        /*
         * 不是第一次审核通过，查询第一次审核通过所有的图片积分记录
         * img_pass 普通正常图片积分
         * activity_img_pass 满足活动的图片积分
         * old_data_pass 积分商城上线前的图片积分记录，只用于记录，在图片再次被操作的时候用，前端不展示
         * old_img_pass 只针对积分上线前的图片再次被审核通过，要用于展示
         */
        
        
        $point_pass_arr = [
            'img_pass', 'activity_img_pass', 'old_data_pass', 'old_img_pass',
        ];
        $point_pass_str = implode("','", $point_pass_arr);
        $points_sql = " SELECT * FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE operate_info->'img_id' = '{$params['img_id']}' 
                    AND operate_source IN ('{$point_pass_str}')
                    AND is_first_pass = 't' 
                    ORDER BY id ASC ";
        $points_rs = DB::GetQueryResult($points_sql, false);
        
        //如果没有查询到，则是之前的数据
        if( !$points_rs ){
            $points_sql = " SELECT * FROM "
                    . QImgSearch::$pointsTraceTableName 
                    ." WHERE operate_info->'img_id' = '{$params['img_id']}' 
                        AND operate_source IN ('{$point_pass_str}')
                        ORDER BY id ASC 
                        LIMIT 1 ";
            $points_rs = DB::GetQueryResult($points_sql, false);
        }
        
        return [
            'is_first' => !$points_rs ? true : false,
            'points_list' => $points_rs ? $points_rs : [],
        ];
    }
    
    /*
     * 获取图片操作来源
     */
    public static function getOperateSource($params=[]){
        $old_operate_source = $params['operate_source'];
        $new_operate_type = $params['operate_type'];
        
        //去掉后面的pass、只留前面的
        $operate_source_rs = explode("_", $old_operate_source);
        array_pop($operate_source_rs);
        $operate_source_tmp = implode("_", $operate_source_rs);
        //原始数据二次处理和第一次脚本处理的标记区分
        if( $operate_source_tmp == 'old_data' ){
            $operate_source_tmp = 'old_img';
        }
        $new_operate_source = $operate_source_tmp."_".$new_operate_type;
        
        return $new_operate_source;
    }
}
