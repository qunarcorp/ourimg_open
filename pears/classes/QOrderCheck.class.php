<?php

/**
 * 用户兑换订单信息验证
 */

class QOrderCheck {
    
    public static $log_prefer = 'img';
    public static $log_action = 'exchange_order_check';

    //验证用户收货信息
    public static function checkAddress($params=[]){
        $address = $params['address'];
        $mobile = $params['mobile'];
        if( !$address ){
            return [
                'status' => 1001,
                'message' => '收货地址不能为空',
            ];
        }
        if( !$mobile ){
            return [
                'status' => 1002,
                'message' => '收货人联系方式不能为空',
            ];
        }
        if( strlen($mobile) != 11 ){
            return [
                'status' => 1017,
                'message' => '收货人联系方式格式有误',
            ];
        }
        
        //正则验证手机号
        if( !preg_match('/^1[34578][\d]{9}$/', $mobile) ){
            return [
                'status' => 1018,
                'message' => '收货人联系方式输入有误，请重试',
            ];
        }
        
        return [
            'status' => 0,
            'message' => '收货信息验证成功',
            'data' => [
                'mobile' => Utility::encryptMobile($mobile),
                'address' => $address,
            ],
        ];
    }
    
    //验证用户可用积分
    public static function checkUserPoints($params=[]){
        global $login_user_name;
        $product_points = $params['product_points'];//每个商品的积分数
        $product_num = $params['product_num'];//商品数量
        $userinfo_rs = QImgPersonal::getUserInfo(['username'=> $login_user_name]);
        
        $user_current_points = $userinfo_rs['points_info']['current_points'];
        $product_all_points = $product_points * $product_num;
        QLog::info(self::$log_prefer,self::$log_action,"用户当前可用积分信息：". var_export($userinfo_rs, true));
        if( $user_current_points < $product_all_points ){
            QLog::info(self::$log_prefer,self::$log_action,"积分不足：用户当前可用积分{$user_current_points}--商品总需要积分{$product_all_points}");
            return [
                'status' => 1003,
                'message' => '用户积分不足，请重试',
                'data' => [
                    'current_points' => $user_current_points,
                    'product_all_points' => $product_all_points,
                ],
            ];
        }
        return [
            'status' => 0,
            'message' => '用户积分够用',
            'data' => [
                'current_points' => $user_current_points,
            ],
        ];
    }
    
    //验证商品库存
    public static function checkProductStock($params=[]){
        $product_num = $params['product_num'];//商品数量
        $product_eid = $params['product_eid'];//商品加密id
        $product_sql = "SELECT * FROM "
                .QImgSearch::$productTableName
                ." WHERE eid = '{$product_eid}' LIMIT 1 ";
        QLog::info(self::$log_prefer,self::$log_action,"查询商品信息，sql如下：". $product_sql);
        $product_rs = DB::GetQueryResult($product_sql);
        if( !$product_rs ){
            QLog::info(self::$log_prefer,self::$log_action,"该商品不存在");
            return [
                'status' => 1004,
                'message' => '商品信息有误，请重新选择',
            ];
        }
        
        QLog::info(self::$log_prefer,self::$log_action,"商品信息如下：". var_export($product_rs, true));
        if( $product_rs['remain_stock'] <= 0 ){
            QLog::info(self::$log_prefer,self::$log_action,"该商品当前无可用库存");
            return [
                'status' => 1008,
                'data' => [
                    'product_id' => $product_rs['id'],
                ],
                'message' => '该商品当前无可用库存，请重新选择',
            ];
        }
        
        if( $product_rs['on_sale'] != 't' ){
            QLog::info(self::$log_prefer,self::$log_action,"该商品状态不可用");
            return [
                'status' => 1009,
                'data' => [
                    'product_id' => $product_rs['id'],
                ],
                'message' => '该商品状态不可用，请重新选择',
            ];
        }

        //有有效期限制
        if( $product_rs['exchange_begin_time'] && $product_rs['exchange_begin_time'] != '1970-01-01 08:00:00+08' ){
            if( $product_rs['exchange_begin_time'] > date("Y-m-d H:i:s") ){
                QLog::info(self::$log_prefer,self::$log_action,"该商品还未上线");
                return [
                    'status' => 1010,
                    'data' => [
                        'product_id' => $product_rs['id'],
                    ],
                    'message' => '该商品还未上线，请重新选择',
                ];
            }


            if( $product_rs['exchange_end_time'] < date("Y-m-d H:i:s") ){
                QLog::info(self::$log_prefer,self::$log_action,"该商品不上线");
                return [
                    'status' => 1011,
                    'data' => [
                        'product_id' => $product_rs['id'],
                    ],
                    'message' => '该商品已下线，请重新选择',
                ];
            }
        }

        return [
            'status' => 0,
            'message' => '商品库存验证通过',
        ];
    }
    
}
