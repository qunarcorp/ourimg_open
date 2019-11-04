<?php

/**
 * 积分商城上线前 已有图片积分处理
 */

class QPointOld {
    public static $log_prefer = 'img_operate';
    public static $log_action = 'points';
    
    /*
     * 现有在线已审核通过图片积分处理--原始数据处理，汇总积分数据
     */
    public static function imgOldPointDeal( $params = [] ){
        
        $operate_username = $params['operate_username'];//操作用户名
        $username = $params['username'];//积分用户名
        $img_id = $params['img_id'];//图片id
        $operate_source = $params['operate_type'];//操作类型
        $change_points = $params['change_points'];//积分数量
        
        //更新积分
        $params_insert = [
            'operate_username' => $operate_username,
            'img_id' => (int)$img_id,
            'operate_source' => $operate_source,
            'username' => $username,
            'change_points' => $change_points,
        ];
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分统计信息如下：". var_export($params_insert, true));
        $insert_rs = QImgPoints::pointInsert($params_insert);
        return $insert_rs ? true : false;
    }
    
    /*
     * 计分操作计算--原始数据处理
     * 记录记录，只为后来图片处理的时候积分计算方便，不做展示
     */
    public static function pointOldInsert( $params = [] ){
        $username = $params['username'];
        $img_id = $params['img_id'];
        $change_points = $params['change_points'];
        
        //事务操作--外部有事务，内部不单独处理事务
        //积分流水表
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分更新信息如下：开始更新");
        
        //json数据记录
        $operate_info_arr = [];
        if( $img_id ){
            $operate_info_arr['img_id'] = (int)$img_id;//图片id
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
            ) VALUES ( 
            '{$username}'
            , '{$params['operate_username']}'
            , 0
            , {$change_points}
            , '{$params['operate_source']}'
            , '{$operate_info}'
            , now()
            ) RETURNING id ";
        
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：". $insert_sql);
        $insert_rs = DB::Query($insert_sql);
        $insert_id = pg_fetch_row($insert_rs);
        
        if( !$insert_id ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：积分插入失败");
            return false;
        }
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：用户积分更新成功");
        return true;
    }
}
