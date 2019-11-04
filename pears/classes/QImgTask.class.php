<?php

/**
 * 图片任务
 */

class QImgTask {
    
    public static $log_prefer = 'img';
    public static $log_action = 'task';
    
    /*
     * 图片上传任务--此时不需要计算上传积分，只看任务
     * --用户只要提交审核就算任务，不需要审核
     * 上传任务积分，操作用户即 积分用户，或可从图片中获取
     */
    public static function uploadTasks( $params = [] ){
        $username = $params['username'];
        $operate_username = $params['operate_username'];
        $img_id = $params['img_id'];
        
        //查询用户当前是否有任务记录
        $today_begin_time = date("Y-m-d 00:00:00");
        $user_task_sql = " SELECT *, array_to_json(img_id) AS img_id_arr FROM ".QImgSearch::$dailyTaskTableName
                ." WHERE username = '{$operate_username}' 
                    AND create_time >= '{$today_begin_time}' 
                   AND task_name = 'upload' AND activity_id IS NULL ";
        $user_task_rs = DB::GetQueryResult($user_task_sql);
        
        /*
         * 判断：
         * --如果用户没有当天对应完成的任务，直接插入
         * --如果有，检查任务是否完成
         * ----如果完成，不更新任务表、不计算积分
         * ----如果没完成，更新任务表，判断当前是否满足10分
         * ------满足10分，加积分
         * ------不满足10分，不加积分
         */
        if( !$user_task_rs ){
            //当前没有在完成的任务--insert上传任务
            $img_id_arr = "{".$img_id."}";
            $insert_arr = [
                'username' => $username,
                'task_name' => 'upload',
                'complete_num' => 1,
                'complete_state' => 'doing',
                'city_name' => '',
                'img_id' => $img_id_arr,
                'create_time' => date("Y-m-d H:i:s"),
                'update_time' => date("Y-m-d H:i:s"),
            ];
            $insert_rs = DB::Insert(QImgSearch::$dailyTaskTableName, $insert_arr, 'id');
            
            $message = "用户{$username}当天没有上传任务记录，查询sql：". $user_task_sql."；新增任务信息：". var_export($insert_arr, true);
            QLog::info(self::$log_prefer,self::$log_action, $message);
            if( !$insert_rs ){
                $message = "新增任务失败";
                QLog::info(self::$log_prefer,self::$log_action, $message);
                return false;
            }
            
            return true;
        }else{
            //判断当前状态
            if( $user_task_rs['complete_state'] == 'done' ){
                //上传任务已完成
                $message = "用户上传任务已经完成，不需要更新任务";
                QLog::info(self::$log_prefer,self::$log_action, $message);
                return true;
            }
                
            //update上传任务
            $current_img_id = json_decode($user_task_rs['img_id_arr'], true);
            $current_img_id[] = $img_id;
            $img_id_arr = "{".implode(",",$current_img_id)."}";

            $update_sql = "UPDATE ". QImgSearch::$dailyTaskTableName
                    ." SET complete_num = complete_num + 1 
                      , complete_state = CASE WHEN complete_num + 1 = 10 THEN 'done' ELSE 'doing' END 
                      , update_time = now() 
                      , img_id = '{$img_id_arr}' 
                      WHERE id = '{$user_task_rs['id']}' 
                          AND complete_num <= 9 
                          RETURNING complete_num, id ";
            $update_rs = DB::Query($update_sql);

            //读取update返回信息
            $update_info = pg_fetch_row($update_rs);
            $complete_num = $update_info[0];
            if( !$complete_num ){
                $message = "更新用户任务数据失败，sql：". var_export($update_sql, true);
                QLog::info(self::$log_prefer,self::$log_action, $message);
                return false;
            }
            
            
            //计算积分--如果当前已经满足十个图片
            if( $complete_num >= 10 ){
                $update_task_id = $update_info[1];
                $params_point = [
                    'username' => $username,
                    'task_id' => $update_task_id,
                    'operate_username' => $operate_username,
                    'operate_source' => 'upload_task',
                ];

                $upload_point_rs = QImgPoints::taskPointDeal($params_point);
                return $upload_point_rs ? true : false;
            }
            
            
            return true;
        }
    }
    
    /*
     * 任务调用处理
     * 状态：用户提交审核
     */
    
    public static function taskQueueDeal( $params = [] ){
        
        //判断只有第一次提交计算任务积分
        $audit_recode_sql = " SELECT * FROM ". QImgSearch::$auditRecordsTableName 
                ." WHERE img_id = '{$params['img_id']}' AND operate_type = 'submit' LIMIT 1 ";
        $audit_recode_rs = DB::GetQueryResult($audit_recode_sql);
        if( $audit_recode_rs ){
            return true;
        }
        
        //验证该图片是否曾经进入过任务队列，避免一天操作多次提交审核多次计入积分
        $daily_sql = " SELECT * FROM ".QImgSearch::$dailyTaskTableName
                ." WHERE {$params['img_id']} = ANY (img_id) LIMIT 1 ";
        $daily_rs = DB::GetQueryResult($daily_sql);
        if( $daily_rs ){
            return true; 
        }
        
        //上传任务统计
        $upload_task_rs = self::uploadTasks($params);
        
        return $upload_task_rs ? true : false;
        
    }
    
    //获取用户当前完成任务的列表情况
    public static function getUserTasks($params=[]){
        global $login_user_name;
        $task_name = $params['task_name'] ? $params['task_name'] : '';


        $task_sql = " SELECT * FROM ". QImgSearch::$dailyTaskTableName 
            ." WHERE username = '{$login_user_name}' AND create_time >= current_date ";
            
        if( $task_name ){
            $task_sql.= " AND task_name = '{$task_name}' AND activity_id IS NULL ";
        }
            
        $task_rs = DB::GetQueryResult($task_sql, false);
        
        return $task_rs ? $task_rs : [];
    }
}
