<?php

/**
 * 我的收藏
 */

class QImgMyFavorite {
    /*
     * 批量操作-用户取消收藏
     */
    public static function cancelFavoriteAll($params=[]){
        $favorite_type = $params['favorite_type'] ? $params['favorite_type'] : 'img';
        $eid = $params['eid'] ? $params['eid'] : [];
        $username = $params['username'] ? $params['username'] : '';//用户名
        if( !$eid || !$username ){
            return [
                'ret' => false,
                'msg' => '参数有误，请稍后重试',
            ];
        }
        
        //获取图片id数组
        $params = [
            "eid" => $eid,
        ];
        $img_info = QImgSearch::getImgsByeid($params);
        if( !$img_info ){
            return [
                'ret' => false,
                'msg' => '信息有误，请稍后重试',
            ];
        }
        $img_id_arr = array_column($img_info, 'id');
        $img_id_str = implode("','", $img_id_arr);
        
        $error_msg = '';
        
        //检查用户是否已经收藏过
        $select_sql = "SELECT id, img_id FROM ".QImgSearch::$favoriteTableName." WHERE img_id IN ('{$img_id_str}') "
        . " AND username = '{$username}' and favorite_type = '{$favorite_type}' ";
        $favorite_rs = DB::GetQueryResult($select_sql,false);
        if( !$favorite_rs ){
            return [
                'ret' => true,
                'msg' => '您还没有收藏过，请前往收藏',
            ];
        }
        
        //去除没有收藏过的图片
        $exist_id_arr = array_column($favorite_rs, 'id');//收藏id
        $exist_id_str = implode("','", $exist_id_arr);
        $exist_img_id_arr = array_column($favorite_rs, 'img_id');//图片id
        $exist_img_id_str = implode("','", $exist_img_id_arr);
        
        //更新收藏表
        $exist_id_arr = array_column($favorite_rs, 'id');
        $exist_id_str = implode("','", $exist_id_arr);
        $del_sql = " DELETE FROM ". QImgSearch::$favoriteTableName." WHERE id IN ('{$exist_id_str}') ";
        $db_rs = DB::Query($del_sql);
        
        //更新图片表
        if( $favorite_type == "img" ){
            $table_name = QImgSearch::$imgTableName;
        }else{
            $table_name = QImgSearch::$albumTableName;
        }
        $del_count = count($exist_id_str);
        $update_sql = " UPDATE ".$table_name
                ." SET favorite = favorite - {$del_count}, update_time = now() WHERE id IN ('{$exist_img_id_str}') ";
        DB::Query($update_sql);
        
        return [
            'ret' => true,
            'msg' => '取消成功',
        ];
    }
    /*
     * 清空用户收藏数据--已下架图片
     */
    public static function delFavotites($params=[]){
        $favorite_type = $params['favorite_type'] ? $params['favorite_type'] : 'img';
        $username = $params['username'] ? $params['username'] : '';//用户名
        if( !$username ){
            return [
                'status' => 101,
                'msg' => '参数有误，请稍后重试',
            ];
        }
        
        //查询img表中数据id
        $select_sql = " SELECT id FROM ". QImgSearch::$imgTableName." WHERE audit_state = '4' AND is_del = 't' ";
        
        //更新收藏表
        $del_sql = " DELETE FROM ". QImgSearch::$favoriteTableName
                ." WHERE username = '{$username}' AND favorite_type = '{$favorite_type}' AND id IN ({$select_sql}) ";
        $db_rs = DB::Query($del_sql);
        
        return [
            'status' => 0,
            'msg' => '操作成功',
        ];
    }
    
    /*
     * 获取我的收藏列表
     */
    public static function getMyFavorite($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        $keyword = $params['keyword'] ? $params['keyword'] : "";
        $offset = $params['offset'] ? $params['offset'] : '';//偏移量-分页用
        $limit = $params['limit'] ? $params['limit'] : '';//数量限制-分页用
        $sort_by = $params['sort_by'] ? $params['sort_by'] : '';//排序
        unset($params['offset']);
        unset($params['limit']);
        unset($params['sort_by']);
        if( !$username ){
            return false;
        }
        
        $sql = "SELECT i.* , array_to_json(keyword) AS keyword_arr FROM ".QImgSearch::$imgTableName." i JOIN "
                .QImgSearch::$favoriteTableName
                ." f ON i.id = f.img_id ";
        
        $sql.= QImgPersonal::getJoinSqlWhere($params);
                
        //排序
        $sql.= QImgSearch::getOrderSql(['sort_by'=>$sort_by,'table_code'=>'i']);
        //分页用
        if( $offset > 0 ){
            $sql.= " OFFSET {$offset}";
        }
        if( $limit > 0 ){
            $sql.= " limit {$limit}";
        }

        $db_rs = DB::GetQueryResult($sql,false);
        
        return $db_rs ? $db_rs : [];
    }
    
    /*
     * 获取我的收藏列表数量
     */
    public static function getMyFavoriteCount($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        if( !$username ){
            return false;
        }
        
        $sql = "SELECT COUNT(1) FROM ".QImgSearch::$imgTableName." i JOIN "
                .QImgSearch::$favoriteTableName." f ON i.id = f.img_id ";
        
        $sql.= QImgPersonal::getJoinSqlWhere($params);
                
        $db_rs = DB::GetQueryResult($sql);
        
        return $db_rs['count'] ? $db_rs['count'] : 0;
    }
    
    /*
     * 用户取消收藏
     */
    public static function cancelFavorite($params=[]){
        $favorite_type = $params['favorite_type'] ? $params['favorite_type'] : 'img';
        $eid = $params['eid'] ? $params['eid'] : 0;
        $username = $params['username'] ? $params['username'] : '';//用户名
        if( !$eid || !$username ){
            return [
                'ret' => false,
                'msg' => '参数有误，eid和username不能为空',
            ];
        }
        //获取图片id
        $params = [
            "eid" => $eid,
        ];
        $img_info = QImgSearch::getOneImg($params);
        if( !$img_info ){
            return [
                'ret' => false,
                'msg' => '信息有误，请稍后重试',
            ];
        }
        $img_id = $img_info['id'];
        
        //检查用户是否已经收藏过
        $select_sql = "SELECT id FROM ".QImgSearch::$favoriteTableName." WHERE img_id = '{$img_id}' "
        . " AND username = '{$username}' and favorite_type = '{$favorite_type}' ";
        $favorite_rs = DB::GetQueryResult($select_sql);
        if( !$favorite_rs ){
            return [
                'ret' => true,
                'msg' => '您还没有收藏过，请前往收藏',
            ];
        }
        
        //更新收藏表
        $del_cond = [
            'id' => $favorite_rs['id'],
            'img_id' => $img_id,
            'username' => $username,
            'favorite_type' => $favorite_type,
        ];
        $db_rs = DB::Delete(QImgSearch::$favoriteTableName, $del_cond);
        
        //更新图片表
        if( $img_info['favorite'] && $img_info['favorite'] > 0 ){
            if( $favorite_type == "img" ){
                $update_sql = " UPDATE ".QImgSearch::$imgTableName
                    ." SET favorite = favorite - 1, update_time = now() WHERE id = '{$img_id}' ";
            }else{
                $update_sql = " UPDATE ".QImgSearch::$imgTableName
                    ." SET favorite = favorite - 1, update_time = now() WHERE id = '{$img_id}' ";
            }
            
        }
        DB::Query($update_sql);
        return [
            'ret' => true,
            'msg' => '取消成功',
        ];
    }
    
    /*
     * 用户收藏
     */
    public static function userFavorite($params=[]){
        $eid = $params['eid'] ? $params['eid'] : 0;
        $username = $params['username'] ? $params['username'] : '';//用户名
        if( !$eid || !$username ){
            return [
                'ret' => false,
                'msg' => '参数有误，eid和username不能为空',
            ];
        }
        //获取图片id
        $params = [
            "eid" => $eid,
        ];
        $img_info = QImgSearch::getOneImg($params);
        if( !$img_info ){
            return [
                'ret' => false,
                'msg' => '信息有误，请稍后重试',
            ];
        }
        $img_id = $img_info['id'];
        
        //检查用户是否已经收藏过
        $select_sql = "SELECT id FROM ".QImgSearch::$favoriteTableName." WHERE img_id = '{$img_id}' "
        . " AND username = '{$username}' ";
        $favorite_rs = DB::GetQueryResult($select_sql);
        if( $favorite_rs ){
            return [
                'ret' => true,
                'msg' => '您已收藏，不需要重复操作',
            ];
        }
        
        DB::TransBegin();
        
        //更新收藏表
        $insert_arr = [
            "img_id" => $img_id,
            "username" => $username,
            "create_time" => date("Y-m-d H:i:s"),
        ];
        $insert_rs = DB::Insert(QImgSearch::$favoriteTableName, $insert_arr, 'id');
        if( !$insert_rs ){
            DB::TransRollback();
            return [
                'ret' => false,
                'msg' => '更新收藏表数据失败',
            ];
        }
        
        //更新图片表
        if( !$img_info['favorite'] ){
            $update_sql = " UPDATE ".QImgSearch::$imgTableName
                ." SET favorite = 1, update_time = now() WHERE id = '{$img_id}' ";
        }else{
            $update_sql = " UPDATE ".QImgSearch::$imgTableName
                ." SET favorite = favorite + 1, update_time = now() WHERE id = '{$img_id}' ";
        }
        $update_rs = DB::Query($update_sql." RETURNING id ");
        $update_rs_arr = pg_fetch_row($update_rs);
        if( !$update_rs_arr[0] ){
            DB::TransRollback();
            return [
                'ret' => false,
                'msg' => '更新图片表失败',
            ];
        }
        
        //计算积分
        $params_point = [
            'username' => $img_info['username'],//积分用户
            'operate_username' => $username,//操作用户
            'img_id' => $img_info['id'],//操作图片id
            'operate_type' => 'favorite',//操作图片类型
        ];
        $task_rs = QImgPoints::pointsDeal($params_point);
        if( !$task_rs ){
            DB::TransRollback();
            return [
                'ret' => false,
                'msg' => '计算积分失败',
            ];
        }
        
        DB::TransCommit();
        return [
            'ret' => true,
            'msg' => '操作成功',
        ];
    }
}
