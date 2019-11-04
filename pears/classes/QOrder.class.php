<?php

/**
 * 商品兑换订单
 */

class QOrder {
    
    public static $log_prefer = 'img';
    public static $log_action = 'exchange_order';
    public static $error_code = [
        1001 => '收货地址不能为空',
        1002 => '收货人联系方式不能为空',
        1003 => '用户积分不足，请重试',
        1004 => '商品信息有误，请重新选择',
        1006 => '库存更新有误，商品兑换失败，请重试',
        1007 => '生成订单失败，请重试',
        1008 => '该商品当前无可用库存，请重新选择',
        1009 => '该商品状态不可用，请重新选择',
        1010 => '该商品还未上线，请重新选择',
        1011 => '该商品已下线，请重新选择',
        1012 => '插入积分队列表失败',
        1013 => '更新用户表积分数据失败',
        1014 => '搜索词不能为空',
        1017 => '收货人联系方式格式有误',
        1018 => '收货人联系方式输入有误，请重试',
    ];
    
    /*
     * 生成兑换订单
     */
    public static function exchangeOrder($params=[]){
        
        global $login_user_name;
        QLog::info(self::$log_prefer,self::$log_action,"用户{$login_user_name}正在使用积分兑换商品：开始执行，params入参如下：". var_export($params, true));
        
        //验证用户收货信息
        $address_rs = QOrderCheck::checkAddress($params);
        if( $address_rs['status'] != 0 ){
            QLog::info(self::$log_prefer,self::$log_action,"用户收货信息验证失败，失败信息：". var_export($address_rs, true));
            return $address_rs;
        }
        //重新复赋值
        $params['mobile'] = $address_rs['data']['mobile'];
        $params['address'] = $address_rs['data']['address'];
        
        //验证用户可用积分
        $user_rs = QOrderCheck::checkUserPoints($params);
        if( $user_rs['status'] != 0 ){
            QLog::info(self::$log_prefer,self::$log_action,"用户可用积分验证失败，失败信息：". var_export($user_rs, true));
            return $user_rs;
        }
        
        //验证商品库存
        $product_rs = QOrderCheck::checkProductStock($params);
        if( $product_rs['status'] != 0 ){
            QLog::info(self::$log_prefer,self::$log_action,"商品库存验证失败，失败信息：". var_export($product_rs, true));
            $product_rs['data']['current_points'] = $user_rs['data']['current_points'];
            return $product_rs;
        }
        
        //生成订单
        $order_rs = self::createExchangeOrder($params);
        if( $order_rs['status'] != 0 ){
            QLog::info(self::$log_prefer,self::$log_action,"用户兑换订单失败，失败信息：". var_export($order_rs, true));
            $order_rs['data']['current_points'] = $user_rs['data']['current_points'];
            return $order_rs;
        }
        
        QLog::info(self::$log_prefer,self::$log_action,"用户兑换订单成功，信息如下：". var_export($order_rs, true));
        return [
            'status' => 0,
            'message' => '兑换成功',
            'data' => [
                'current_points' => $order_rs['data']['current_points']
            ],
        ];
        
    }
    
    /*
     * 生成随机订单号
     */
    public static function generateRandomOrderId(){
        
        //年4月2日2时2分2秒2毫秒3
        $mtimestamp = sprintf("%.3f", microtime(true)); // 带毫秒的时间戳

        $timestamp = floor($mtimestamp); // 时间戳
        $milliseconds = round(($mtimestamp - $timestamp) * 1000); // 毫秒

        $datetime = date("YmdHis", $timestamp). $milliseconds;
        
        //生成三位随机数
        $random_num = Utility::GenSecret(3, Utility::CHAR_NUM);
        
        return $datetime.$random_num;
    }
    
    
    //生成订单
    public static function createExchangeOrder($params=[]){
        
        $product_num = $params['product_num'];//商品数量
        $product_eid = $params['product_eid'];//商品加密id
        $product_points = $params['product_points'];//每个商品的积分数
        $mobile = $params['mobile'];//收货手机号
        $address = $params['address'];//收货地址
        
        global $login_user_name;


        //事务
        DB::TransBegin();
        
        //更新商品当前库存
        $product_sql = " UPDATE ".QImgSearch::$productTableName." SET 
            exchange_count = exchange_count + $product_num
            , remain_stock = remain_stock - $product_num
            , update_time = now()
            WHERE eid = '{$product_eid}' AND on_sale = 't' 
                AND (
                ( 
                exchange_begin_time = '1970-01-01 08:00:00+08' 
                OR exchange_begin_time IS NULL 
                )
                OR
                ( exchange_begin_time <= now() AND exchange_end_time >= now() )
                )
                AND remain_stock >= '{$product_num}' RETURNING id ";
        QLog::info(self::$log_prefer,self::$log_action,"更新商品库存，sql如下：". $product_sql);
        $product_rs = DB::Query($product_sql);
        $product_update_info = pg_fetch_row($product_rs);
        $product_id = $product_update_info[0];
        if( !$product_id ){
            DB::TransRollback();
            QLog::info(self::$log_prefer,self::$log_action,"兑换订单失败--原因--更新商品库存失败");
            return [
                'status' => 1006,
                'message' => '库存更新有误，商品兑换失败，请重试',
            ];
        }
        
        //生成订单
        $order_info = [
            'order_id' => self::generateRandomOrderId(),
            'product_id' => $product_id,
            'product_points' => $product_points,
            'exchange_count' => $product_num,
            'exchange_points' => $product_points * $product_num,
            'mobile' => $mobile,
            'address' => $address,
            'state' => 'exchange_success',
            'username' => $login_user_name,
            'create_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
        ];
        QLog::info(self::$log_prefer,self::$log_action,"生成兑换订单，订单信息如下：". var_export($order_info, true));
        $order_rs = DB::Insert(QImgSearch::$orderTableName, $order_info, 'id');
        if( !$order_rs ){
            DB::TransRollback();
            QLog::info(self::$log_prefer,self::$log_action,"兑换订单失败--原因--生成兑换订单记录失败");
            return [
                'status' => 1007,
                'message' => '生成订单失败，请重试',
            ];
        }
        
        //积分变更--订单信息回填到积分表中
        $params_point = [
            'operate_username' => $login_user_name,
            'product_eid' => $product_eid,
            'product_num' => $product_num,
            'orderid' => $order_rs,
            'operate_source' => 'exchange',
            'username' => $login_user_name,
            'change_points' => 0 - ($product_points * $product_num),
        ];
        
        //兑换的积分
        QLog::info(self::$log_prefer,self::$log_action,"积分变更信息：". var_export($params_point, true));
        $point_insert_rs = self::pointInsert($params_point);
        if( $point_insert_rs['status'] !== 0 ){
            DB::TransRollback();
            QLog::info(self::$log_prefer,self::$log_action,"兑换订单失败--原因--积分变更失败");
            return $point_insert_rs;
        }
        
        
        DB::TransCommit();
        QLog::info(self::$log_prefer,self::$log_action,"兑换订单生成成功");
        return [
            'status' => 0,
            'message' => '生成订单成功',
            'data' => [
                'current_points' => $point_insert_rs['data']['current_points']
            ],
        ];
    }
    
    /*
     * 计分操作计算
     */
    public static function pointInsert( $params = [] ){
        $username = $params['username'];
        $change_points = $params['change_points'];
        $product_eid = $params['product_eid'];
        $product_num = $params['product_num'];
        $orderid = $params['orderid'];
        
        //查询图片之前的积分汇总--查上次的记录即可
        $point_last_trace_sql = " SELECT current_points + {$change_points} AS old_points FROM "
                . QImgSearch::$userTableName 
                ." WHERE username = '{$username}' LIMIT 1 ";

        //事务操作--外部有事务，内部不单独处理事务
        //积分流水表
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分更新信息如下：开始更新");
        $operate_info_arr = [
            'product_eid' => $product_eid ? $product_eid : '',//商品eid
            'product_num' => $product_num ? $product_num : 0,//商品数量
            'orderid' => $orderid ? $orderid : 0,//订单id
        ];
        
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
            , ({$point_last_trace_sql})
            , {$change_points}
            , '{$params['operate_source']}'
            , '{$operate_info}'
            , now()
            ) RETURNING id ";
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：". var_export($insert_arr, true));
        $insert_rs = DB::Query($insert_sql);
        $insert_id = pg_fetch_row($insert_rs);
        
        if( !$insert_id[0] ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：积分插入失败");
            return [
                'status' => 1012,
                'msg' => '插入积分队列表失败',
            ];
        }
        
        //更新用户表积分数据
        $user_points_update_sql = " UPDATE ". QImgSearch::$userTableName 
            ." SET total_points = CASE WHEN {$change_points} > 0 THEN total_points + {$change_points} ELSE  total_points END 
            , current_points = current_points + {$change_points}
            , last_date_points = CASE WHEN last_point_date = current_date THEN last_date_points + {$change_points}
                ELSE {$change_points} END 
            , last_point_date = current_date
             WHERE username = '{$username}' AND current_points >= '{$change_points}' RETURNING id, current_points ";
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}积分更新sql如下：". $user_points_update_sql);
        $update_rs = DB::Query($user_points_update_sql);
        $update_rs_arr = pg_fetch_row($update_rs);
        $update_id = $update_rs_arr[0];
        $current_points = $update_rs_arr[1];
        
        if( !$update_id ){
            QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：用户表积分汇总信息更新失败");
            return [
                'status' => 1013,
                'msg' => '更新用户表积分数据失败',
            ];
        }
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$username}新积分信息如下：用户积分更新成功");
        return [
            'status' => 0,
            'msg' => '操作成功',
            'data' => [
                'current_points' => $current_points,
            ],
        ];
    }
    
}
