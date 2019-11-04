<?php

/**
 * 积分数据查询
 */

class QPointInfo {
    
    /*
     * 获取积分排行榜
     */
    public static function getPointBoard($params=[]){
        $board_sql = " SELECT username, name, total_points FROM ". QImgSearch::$userTableName 
                ." WHERE total_points > 0 and username != 'piao_qsight_provider' ORDER BY total_points DESC LIMIT 5 ";
        $board_rs = DB::GetQueryResult($board_sql, false);
        return $board_rs ? $board_rs : [];
    }
    
    /*
     * 获取用户积分列表
     */
    public static function getUserPointsTrace($params=[]){
        global $login_user_name;
        $time_select = $params['time_select'] ? $params['time_select'] : 'one_month';
        $point_type = $params['point_type'] ? $params['point_type'] : '';
        $limit = $params['limit'] ? $params['limit'] : 100;
        $offset = $params['offset'] ? $params['offset'] : 0;
        $points_sql = " SELECT *
            , operate_info->>'img_id' AS img_id
            , operate_info->>'city_name' AS city_name
            , operate_info->>'product_eid' AS product_eid FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE username = '{$login_user_name}' AND operate_source != 'old_data_pass' ";
                
        
        $points_sql_where = self::getSqlWhere($params);
        $points_sql.= $points_sql_where;
                
                
        $points_sql.= " ORDER BY id DESC ";
        $points_sql.= " LIMIT {$limit} ";
        $points_sql.= " OFFSET {$offset} ";
        
        
        $points_rs = DB::GetQueryResult($points_sql, false);
        
        //获取图片标题
        $img_id_arr = array_filter(array_column($points_rs, 'img_id'));
        if( $img_id_arr ){
            $params_id = [
                'id' => $img_id_arr,
            ];
            $img_info_arr = QImgSearch::getImgsByeid($params_id);

            $img_info_rs_id = array_column($img_info_arr, 'id');
            $img_info_rs_title = array_column($img_info_arr, 'title');
            $img_title_arr = array_combine($img_info_rs_id, $img_info_rs_title);
        }
        
        //获取活动任务标题
        $activity_id_arr = array_filter(array_column($points_rs, 'activity_id'));
        if( $activity_id_arr ){
            $params_id = [
                'id' => $activity_id_arr,
            ];
            $activity_info_arr = QActivity_TaskPoint::getActivities($params_id);

            $activity_info_rs_id = array_column($activity_info_arr, 'id');
            $activity_info_rs_title = array_column($activity_info_arr, 'activity_title');
            $activity_title_arr = array_combine($activity_info_rs_id, $activity_info_rs_title);
        }
        
        
        
        
        //获取产品标题
        $product_eid_arr = array_filter(array_column($points_rs, 'product_eid'));
        
        if( $product_eid_arr ){
            $product_eid_str = implode("','", $product_eid_arr);
            $product_sql = " SELECT * FROM ".QImgSearch::$productTableName." WHERE eid IN ('".$product_eid_str."') ";
            $product_rs = DB::GetQueryResult($product_sql, false);
            
            $product_info_rs_eid = array_column($product_rs, 'eid');
            $product_info_rs_title = array_column($product_rs, 'title');
            $product_title_arr = array_combine($product_info_rs_eid, $product_info_rs_title);
            
        }
        
        
        
        $rs = [
            'point_list' => $points_rs,
            'img_title' => $img_title_arr,
            'activity_title' => $activity_title_arr,
            'product_title' => $product_title_arr,
        ];
        return $rs;
    }
    
    /*
     * 整理sql-where语句
     */
    public static function getSqlWhere($params=[]){
        $time_select = $params['time_select'] ? $params['time_select'] : 'one_month';
        $point_type = $params['point_type'] ? $params['point_type'] : '';
        
        $points_sql = '';
                
        //积分类型：收入|支出
        if( $point_type == 'income' ){//收入
            $points_sql.= " AND change_points > 0 ";
        }elseif( $point_type == 'expenses' ){//支出
            $points_sql.= " AND change_points <= 0 ";
        }
        
        //时间限制
        if( $time_select == 'one_month' ){
            $point_begin_time = date("Y-m-d H:i:s", strtotime(" -1 month "));
            $points_sql.= " AND create_time >= '{$point_begin_time}' ";
        }elseif( $time_select == 'three_month' ){
            $point_begin_time = date("Y-m-d H:i:s", strtotime(" -3 month "));
            $points_sql.= " AND create_time >= '{$point_begin_time}' ";
        }elseif( $time_select == 'half_year' ){
            $point_begin_time = date("Y-m-d H:i:s", strtotime(" -6 month "));
            $points_sql.= " AND create_time >= '{$point_begin_time}' ";
        }elseif( $time_select == 'one_year' ){
            $point_begin_time = date("Y-m-d H:i:s", strtotime(" -1 year "));
            $points_sql.= " AND create_time >= '{$point_begin_time}' ";
        }
        
        
        
        return $points_sql ? $points_sql : '';
    }
    
    /*
     * 获取用户积分列表的数量
     */
    public static function getUserPointsCount($params=[]){
        global $login_user_name;
        $points_sql = " SELECT COUNT(1) AS count FROM "
                . QImgSearch::$pointsTraceTableName 
                ." WHERE username = '{$login_user_name}' 
                    AND operate_source != 'old_data_pass'";
                
        $points_sql_where = self::getSqlWhere($params);
        $points_sql.= $points_sql_where;
        
        $points_rs = DB::GetQueryResult($points_sql);
        
        return $points_rs['count'] > 0 ? $points_rs['count'] : 0;
    }
}
