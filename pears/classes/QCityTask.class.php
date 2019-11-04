<?php

/**
 * 城市任务
 */

class QCityTask {
    
    public static $log_prefer = 'img';
    public static $log_action = 'task';
    
    /*
     * 判断是否满足城市任务、是否是首图
     */
    
    public static function cityImgTaskCheck( $params = [] ){
        $img_id = $params['img_id'];
        $img_info = $params['img_info'];
        //获取图片城市地点信息
        if( !$img_info || !$img_info['city'] ){
            $params_img = [
                'id' => $img_id,
            ];
            $img_info = QImgSearch::getOneImg($params_img);

            if( !$img_info['city'] ){
                //退出
                return [
                    'ret' => false,
                    'msg' => '图片城市信息有误',
                    'data' => [],
                ];
            }
        }
        
        
        //验证是否是完成任务
        $city_sql = " SELECT * FROM ". QImgSearch::$taskCityTableName 
                ." WHERE city_name = '{$img_info['city']}' 
                    AND begin_time <= '{$img_info['create_time']}' AND end_time >= '{$img_info['create_time']}'  ";
        $city_rs = DB::GetQueryResult($city_sql);
        //不是首图任务
        if( !$city_rs ){
            return [
                'ret' => true,
                'msg' => '查询成功',
                'data' => [
                    'is_city_task' => false,//是否是城市任务
                    'is_city_first_img' => false,//是否是城市首图
                    'is_city_myfirst_img' => false,//是否是我上传的城市首图
                    'operate_source_pre' => 'img_',//操作类型前缀
                    'city_name' => $img_info['city'],//城市名称
                ],
            ];
        }
        
        //以下是是首图任务的处理
        
        //城市首图--还没有人上传
        if( $city_rs['first_img_state'] != 'done' ){
            return [
                'ret' => true,
                'msg' => '查询成功',
                'data' => [
                    'is_city_task' => true,//是否是城市任务
                    'is_city_first_img' => true,//是否是城市首图
                    'is_city_myfirst_img' => true,//是否是我上传的城市首图
                    'operate_source_pre' => 'city_first_',//操作类型前缀
                    'city_name' => $img_info['city'],//城市名称
                ],
            ];
        }
        
        /*
         * 是首图任务，但不是[城市]首图，是个人上传的城市首图
         * 判断用户是否上传过该城市的图片
         */
        $points_trace_sql = "SELECT * FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE username = '{$img_info['username']}' 
                AND operate_source = 'cith_first_img' 
                AND operate_info->>'city_name' = '{$img_info['city']}' " ;
        
        $points_trace_rs = DB::GetQueryResult($points_trace_sql);
        
        if( !$points_trace_rs ){
            return [
                'ret' => true,
                'msg' => '查询成功',
                'data' => [
                    'is_city_task' => true,//是否是城市任务
                    'is_city_first_img' => false,//是否是城市首图
                    'is_city_myfirst_img' => true,//是否是我上传的城市首图
                    'operate_source_pre' => 'city_myfirst_',//操作类型前缀
                    'city_name' => $img_info['city'],
                ],
            ];
        }

        return [
            'ret' => true,
            'msg' => '查询成功',
            'data' => [
                'is_city_task' => true,
                'is_city_first_img' => false,
                'is_city_myfirst_img' => false,//是否是我上传的城市首图
                'operate_source_pre' => 'img_',//操作类型前缀
                'city_name' => $img_info['city'],//城市名称
            ],
        ];
    }
    
    /*
     * 验证城市任务-新 2019-03-05
     */
    
    public static function getImgOperate( $params = [] ){
        $img_id = $params['img_id'];
        $img_info = $params['img_info'];
        //获取图片城市地点信息
        if( !$img_info || !$img_info['city'] ){
            $params_img = [
                'id' => $img_id,
            ];
            $img_info = QImgSearch::getOneImg($params_img);

            if( !$img_info['city'] ){
                //退出
                return [
                    'ret' => false,
                    'msg' => '图片城市信息有误',
                    'data' => [
                        'is_city_task' => false,
                        'operate_source_pre' => 'img_',//操作类型前缀
                    ],
                ];
            }
        }
        
        //验证是否是完成任务
        $city_sql = " SELECT * FROM ". QImgSearch::$taskCityTableName 
                ." WHERE city_name = '{$img_info['city']}' 
                    AND begin_time <= '{$img_info['create_time']}' 
                        AND end_time >= '{$img_info['create_time']}' ";
        $city_rs = DB::GetQueryResult($city_sql);
        //不是首图任务
        if( !$city_rs ){
            return [
                'ret' => true,
                'msg' => '查询成功',
                'data' => [
                    'is_city_task' => false,
                    'operate_source_pre' => 'img_',//操作类型前缀
                    'city_name' => $img_info['city'],//城市名称
                ],
            ];
        }

        return [
            'ret' => true,
            'msg' => '查询成功',
            'data' => [
                'is_city_task' => true,
                'operate_source_pre' => 'img_city_',//操作类型前缀
                'city_name' => $img_info['city'],//城市名称
            ],
        ];
    }
    
    /*
     * 更新城市任务-新 2019-03-05
     */
    
    public static function updateCityInfo( $params = [] ){
        $img_id = $params['img_id'];
        $username = $params['username'];
        $city_name = $params['city_name'];
        
        QLog::info(self::$log_prefer,self::$log_action, "用户{$username}的图片id={$img_id}被审核通过，是城市首图，正在更新城市任务配置表ing");
                    
        $update_arr = [
            'first_img_state' => 'done',
            'first_img_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
            'first_img_username' => $username,
        ];
        $city_task_rs = DB::Update(QImgSearch::$taskCityTableName, $city_name, $update_arr, 'city_name');
        if( !$city_task_rs ){
            $message = "用户{$username}的图片id={$img_id}被审核通过，是城市首图，更新城市任务配置表失败，更新信息如下：". var_export($update_arr, true);
            QLog::info(self::$log_prefer,self::$log_action, $message);
            return false;
        }
        
        return true;
        
    }
    
    /*
     * 图片提交--城市任务
     * --用户只要提交就算任务，不需要审核
     * 操作用户即积分用户
     * --任务积分是当前就给
     * --首图积分 要等审核通过后
     */
    public static function cityTasks( $params = [] ){
        $username = $params['username'];
        $operate_username = $params['operate_username'];
        $img_id = $params['img_id'];
        
        /*
         * 判断城市任务:
         * 如果不是城市任务：返回true，直接退出
         * 如果是，需要修改任务和积分
         */
        
        $city_task_check_rs = self::getImgOperate($params);
        if( $city_task_check_rs['ret'] && $city_task_check_rs['data']['is_city_task'] ){
            /*
             * insert城市任务
             * 查询用户当前是否有城市任务完成的记录
             */
            
            $today_begin_time = date("Y-m-d 00:00:00");
            $user_task_sql = " SELECT * FROM ".QImgSearch::$dailyTaskTableName
                    ." WHERE username = '{$username}' 
                        AND create_time >= '{$today_begin_time}' 
                       AND task_name = 'city' ";
            $user_task_rs = DB::GetQueryResult($user_task_sql);
            if( !$user_task_rs ){
                $insert_arr = [
                    'username' => $username,
                    'task_name' => 'city',
                    'city_name' => $city_task_check_rs['data']['city_name'],//添加城市名称
                    'complete_num' => 1,
                    'complete_state' => 'done',
                    'img_id' => "{".$img_id."}",
                    'create_time' => date("Y-m-d H:i:s"),
                    'update_time' => date("Y-m-d H:i:s"),
                ];
                
                $insert_rs = DB::Insert(QImgSearch::$dailyTaskTableName, $insert_arr, 'id');
                
                //计算积分
                $params_point = [
                    'img_id' => $params['img_id'],
                    'username' => $params['username'],
                    'operate_username' => $params['operate_username'],
                    'city_name' => $city_task_check_rs['data']['city_name'],
                    'operate_source' => 'city_img_task',
                ];
                $upload_point_rs = QImgPoints::taskPointDeal($params_point);
                
                return $upload_point_rs && $insert_rs ? true : false;
            }
            
            return true;
        }
        
        //用户已经存在城市任务|该城市不是任务，不需要修改任务和积分
        return true;
    }
}
