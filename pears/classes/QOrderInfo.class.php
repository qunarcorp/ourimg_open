<?php

/**
 * 兑换订单查询
 */

class QOrderInfo {

    /*
     * 兑换订单query-suggest
     */
    
    public static function exchangeOrderQuerySuggest($params=[]){
        $query = $params['query'];
        
        //查询
        global $login_user_name;
        $query_sql = " SELECT query FROM "
                .QImgSearch::$searchRecordTableName
                ." WHERE username = '{$login_user_name}' 
                    AND source = 'order' ";
                    
        if( $query ){
            $query_sql.= " AND query LIKE '%{$query}%' ";
        }
        $query_sql.= " GROUP BY query ORDER BY MAX(id) DESC LIMIT 10 ";
        
        $query_rs = DB::GetQueryResult($query_sql, false);
        
        $query_arr = array_column($query_rs, 'query');
        return [
            'status' => 0,
            'message' => '查询成功',
            'data' => [
                'query_arr' => $query_arr
            ],
        ];
    }
    
    //获取订单总数量
    public static function getExchangeOrderCount($params=[]){
        $order_sql = " SELECT COUNT(1) AS count  FROM "
                . QImgSearch::$orderTableName
                ." o ";
        
                
        $where_sql = self::getOrderSqlWhere($params);
                
        $order_sql.= $where_sql;
        
        $order_rs = DB::GetQueryResult($order_sql);
        return $order_rs['count'] > 0 ? $order_rs['count'] : 0;
    }
    
    //拼接订单查询sql--普通用户
    public static function getOrderSqlWhere($params=[]){
        
        $where_sql = " WHERE 1 = 1 ";
        
        //普通用户必须
        $username = $params['username'];
        if( $username ){
            $where_sql.= " AND o.username = '{$username}' ";
        }
        
        
        //管理员页面用参数
        $state = $params['state'] ? $params['state'] : '';//订单状态
        $query = $params['query'] ? $params['query'] : '';//订单id\用户名
        
        if( $state && $state != 'all' ){
            $where_sql.= " AND o.state = '{$state}' ";
        }
        if( $query ){
            $where_sql.= " AND (o.order_id LIKE '%{$query}%' 
                OR p.eid::text LIKE '%{$query}%'
                OR p.title LIKE '%{$query}%'
                OR u.name LIKE '%{$query}%'
                OR o.username LIKE '%{$query}%') ";
                
            //记录搜索记录--管理员用
            global $login_user_name;
            $search_info = [
                'query' => $query,
                'source' => 'order',
                'username' => $login_user_name,
                'create_time' => date("Y-m-d H:i:s"),
            ];
            DB::Insert(QImgSearch::$searchRecordTableName, $search_info,'id');
        }
        
        
        return $where_sql;
    }
    
    //获取用户兑换列表
    public static function getExchangeOrders($params=[]){
        $limit = $params['limit'] ? $params['limit'] : 10;
        $offset = $params['offset'] ? $params['offset'] : 0;
       
        
        $order_sql = " SELECT p.img_url AS img_url
               , p.title AS product_title 
               , p.eid AS product_eid 
               , o.order_id AS order_id 
               , o.create_time AS create_time 
               , o.exchange_points AS exchange_points 
               , o.exchange_count AS exchange_count 
               , o.state AS state 
               , o.product_points AS product_points 
               , o.ship_time AS ship_time 
               , o.address AS address 
               , o.mobile AS mobile 
               , u.name AS name 
               , u.username AS username 
            FROM "
                . QImgSearch::$orderTableName
                ." o LEFT JOIN ".QImgSearch::$userTableName
                ." u ON o.username = u.username 
                    LEFT JOIN ".QImgSearch::$productTableName
                ." p ON o.product_id = p.id ";
        
                
        $where_sql = self::getOrderSqlWhere($params);
                
        $order_sql.= $where_sql;
        $order_sql.= " ORDER BY o.id DESC ";
        if( $limit ){
            $order_sql.= " LIMIT {$limit} ";
        }
        if( $offset ){
            $order_sql.= " OFFSET {$offset} ";
        }
        
        $order_rs = DB::GetQueryResult($order_sql, false);
        return $order_rs ? $order_rs : [];
    }
    
    //处理兑换订单展示详情
    public static function dealExchangeOrders($params=[]){
        global $INI;
        global $system_domain;
        $order_list = [];
        foreach( $params as $k => $v ){
            $product_img_url_arr = json_decode($v['img_url'], true);
            $product_img_url = array_shift($product_img_url_arr);
            
            //获取图片原地址
            $product_img = QImg::getImgUrlResize(array(
                    "img" => $product_img_url,
                    "r_width" => 60,
                    "r_height" => 60,
                    'system_domain'=>$system_domain,
                    "in"=>"inner_domain")
                    );//图片上传的缩略图地址或者是缩略图
            
            if( $v['ship_time'] ){
                $ship_date = date("Y-m-d", strtotime($v['ship_time']));
            }else{
                $ship_date = '未发货';
            }
            $order_list[] = [
                'product_img_url' => $product_img,//产品图片
                'order_id' => $v['order_id'],//订单id
                'create_time' => date("Y-m-d H:i:s", strtotime($v['create_time'])),//订单时间
                'create_date' => date("Y-m-d", strtotime($v['create_time'])),//订单日期
                'ship_time' => date("Y-m-d H:i:s", strtotime($v['ship_time'])),//发货日期
                'ship_date' => $ship_date,//发货日期
                'product_title' => $v['product_title'],//产品标题
                'exchange_points' => $v['exchange_points'],//消耗积分
                'exchange_count' => $v['exchange_count'],//兑换数量
                'product_points' => $v['product_points'],//产品单价
                'product_eid' => $v['product_eid'],//产品eid
                'state' => $v['state'],//兑换状态
                'username' => $v['username'],//收货人姓名
                'name' => $v['name'],//收货人姓名
                'address' => $v['address'],//收货人地址
                'mobile' => $v['mobile'],//收货人联系方式
            ];
        }
        
        return $order_list ? $order_list : [];
    }
    
}
