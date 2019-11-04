<?php

/**
 * 精选推荐
 */

class QAudit_Feature {
    
    public static $log_prefer = 'img_feature';//日志目录
    public static $log_action = 'recommend';//日志目录
    
    /**
     * star
     * @param $eid
     * @return bool
     */
    public static function recommend($params=[]){
        global $login_user_name;//当前审核人
        $message = "操作人:".$login_user_name;
        QLog::info(self::$log_prefer, self::$log_action, $message);
        $operate_type = $params['operate_type'];
        $eid = $params['eid'];

        DB::TransBegin();
        
        $img_update_sql = $params['update_sql'];
        $updateResult = DB::Query($img_update_sql);
        
        //判断结果
        $img_id_arr = pg_fetch_all($updateResult);
        if( !$img_id_arr ){
            DB::TransRollback();
            $message = '精选状态更新失败';
            QLog::info(self::$log_prefer, self::$log_action, $message."；失败sql：".$img_update_sql);
            return [
                'status' => 301,
                'message' => $message,
            ];
        }
        
        //插入更新流水表--记录操作流水：精选推荐feature
        $insert_trace_sql = " INSERT INTO ".QImgSearch::$auditRecordsTableName
                ." (img_id,username,operate_type,create_time,old_data,new_data) VALUES  ";
        $insert_trace_sql_tmp = '';
        $imgIds = array_column($img_id_arr, "id");
        $imgIdsStr = array2insql($imgIds);
        $imgSourceSql = <<<SQL
    select id, upload_source from public.img where id in ({$imgIdsStr})
SQL;

        $imgSource = (array) DBSLAVE::GetQueryResult($imgSourceSql, false);
        $imgSource = array_replace_key($imgSource, 'id');

        foreach( $img_id_arr as $k => $v ){
            $isDeptSource = !! $imgSource[$v['id']]["upload_source"];
            if( $insert_trace_sql_tmp ){
                $insert_trace_sql_tmp.= " , ";
            }
            $insert_trace_sql_tmp.= " ({$v['id']}, '{$login_user_name}', '{$operate_type}', current_timestamp, null, null) ";

            if (! $isDeptSource) {
                //计算积分
                $change_points = QImgPoints::$pointConf[$operate_type];
                $params_insert = [
                    'operate_username' => $login_user_name,
                    'img_id' => $v['id'],
                    'operate_source' => $operate_type,
                    'username' => $v['username'],
                    'change_points' => $change_points,
                ];
                $insert_rs = QImgPoints::pointInsert($params_insert);
                if( !$insert_rs ){
                    DB::TransRollback();
                    $message = '精选积分计算失败';
                    QLog::info(self::$log_prefer, self::$log_action, $message."；失败积分数据：". var_export($params_insert, true));

                    return [
                        'status' => 302,
                        'message' => $message,
                    ];
                }
            }
            
            //发送消息
            if( $operate_type == 'recommend' ){
                $message = "您贡献的素材「{$v['title']}」被评为精选推荐素材啦！" . ($isDeptSource ? "" : "{$change_points}奖励积分已到账！ ");
                $message_rs = QImgMessage::addRecommendMessage ($v['username'],$message,'2');
            }else{
                $change_points_abs = abs($change_points) ;
                $message = "您贡献的素材「{$v['title']}」被取消了精选推荐！" . ($isDeptSource ? "" : "{$change_points_abs}奖励积分扣除！ ");
                $message_rs = QImgMessage::unRecommendMessage ($v['username'],$message,'2');
            }

            if( !$message_rs ){
                DB::TransRollback();
                $message = '精选消息发送失败';
                QLog::info(self::$log_prefer, self::$log_action, $message);
                return [
                    'status' => 303,
                    'message' => $message,
                ];
            }
        }
        $insert_trace_sql.= $insert_trace_sql_tmp;
        $insert_rs = DB::Query($insert_trace_sql);
        
        if( !$insert_rs ){
            DB::TransRollback();
            $message = '操作流水记录失败';
            QLog::info(self::$log_prefer, self::$log_action, $message."；失败sql：".$insert_trace_sql);
            return [
                'status' => 304,
                'message' => $message,
            ];
        }
        
        DB::TransCommit();
        if( $operate_type == 'recommend' ) {
            $message = '精选操作成功';
        }else{
            $message = '撤销精选推荐操作成功';
        }


        QLog::info(self::$log_prefer, self::$log_action, $message);
        
        return [
            'status' => 0,
            'message' => $message,
        ];
    }
    
    /*
     * 总方法
     */
    public static function featureRecommend($params=[]){
        $eidStr = array2insql($params['eid']);
        $type = $params['type'];

        //查询当前是否有未处理的审核通过的图片积分
        $pending_rs = QImgPoints::getPendingPassList($params);
        if ($pending_rs) {
            return [
                'status' => 305,
                'message' => '操作精选的图片当前有未处理完的积分，请稍后重试',
            ];
        }
        
        //以下是操作
        if( $type == 'recommend' ){
                //精选推荐
                //精选推荐
                $update_sql = <<<SQL
            update public.img set star = 't', star_time = current_timestamp, update_time = current_timestamp 
            where eid in ({$eidStr}) AND star != 't' AND audit_state = 2 AND is_del != 't' 
            RETURNING id, username, title
SQL;

            $message = "批量精选推荐：eid:". var_export($params['eid'],true)."；sql:".$update_sql;
            QLog::info(self::$log_prefer, self::$log_action, $message);
            $recommend_params = [
                'eid' => $params['eid'],
                'operate_type' => 'recommend',
                'update_sql' => $update_sql,
            ];
            return self::recommend($recommend_params);
            
        }elseif( $type == 'unrecommend' ){
            //取消精选推荐
            $update_sql = <<<SQL
            update public.img set star = 'f', star_time = null , update_time = current_timestamp
            where eid in ({$eidStr}) AND star = 't' AND audit_state = 2 AND is_del != 't' 
            RETURNING id, username, title
SQL;
            
            $message = "批量取消精选推荐：eid:". var_export($params['eid'],true)."；sql:".$update_sql;
            QLog::info(self::$log_prefer, self::$log_action, $message);
            $unrecommend_params = [
                'eid' => $params['eid'],
                'operate_type' => 'unrecommend',
                'update_sql' => $update_sql,
            ];
            return self::recommend($unrecommend_params);
            
        }else{
            return [
                'status' => 300,
                'message' => '操作有误，请重试',
            ];
        }
    }
}
