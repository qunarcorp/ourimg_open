<?php

/**
 * 我的浏览
 */

class QImgMyBrowse {
    /*
     * 删除浏览记录--批量
     * del_type=all清空所有
     */
    public static function delMyBrowses($params=[]){
        $eid = $params['eid'] ? $params['eid'] : [];//eid
        $username = $params['username'] ? $params['username'] : '';//用户名
        $del_type = $params['del_type'] ? $params['del_type'] : '';//删除类型：all全删
        if( !$username ){
            return [
                'ret' => false,
                'msg' => 'username不能为空',
            ];
        }
        
        $del_sql = " DELETE FROM ".QImgSearch::$browseTableName." WHERE username = '{$username}' ";
        if( $del_sql == 'deleted' ){
            $img_sql = "SELECT DISTINCT(id) AS id FROM "
                . QImgSearch::$imgTableName." WHERE audit_state = 4 AND is_del = 't' ";

            $del_sql .= " AND img_id IN ({$img_sql}) ";
        }elseif( $del_type != "all" ){
            if( !$eid || !is_array($eid) ){
                return [
                    'ret' => false,
                    'msg' => 'eid不能为空',
                ];
            }
            $eid_str = implode("','", $eid);
            $sql_where = " WHERE eid IN ('{$eid_str}') ";
            $img_sql = "SELECT DISTINCT(id) AS id FROM "
                    . QImgSearch::$imgTableName.$sql_where;

            $del_sql .= " AND img_id IN ({$img_sql}) ";
        }
        
        $del_rs = DB::Query($del_sql);
        return [
            'ret' => $del_rs ? true : false,
        ];
    }
    
    /*
     * 获取我的浏览列表
     */
    public static function getMyBrowses($params=[]){
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
        
        $sql = "SELECT i.* , array_to_json(keyword) AS keyword_arr FROM "
                .QImgSearch::$imgTableName." i JOIN "
                .QImgSearch::$browseTableName
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
     * 获取我的浏览列表数量
     */
    public static function getMyBrowseCount($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        if( !$username ){
            return false;
        }
        
        $sql = "SELECT COUNT(1) FROM ".QImgSearch::$imgTableName." i JOIN "
                .QImgSearch::$browseTableName." f ON i.id = f.img_id ";
        
        $sql.= QImgPersonal::getJoinSqlWhere($params);
        $db_rs = DB::GetQueryResult($sql);
        
        return $db_rs['count'] ? $db_rs['count'] : 0;
    }
    
    /*
     * 进入详情页增加一次浏览量
     */
    public static function updateBrowseCount($params=[]){
        $eid = $params['eid'] ? $params['eid'] : '';
        if( !$eid ){
            return false;
        }
        
        //登录用户更新浏览记录表
        $username = $params['username'] ? $params['username'] : '';
        if( $username ){
            self::updateBrowseTraceLogin($params);
        }
        
        //更新img表
        $update_sql = " UPDATE ". QImgSearch::$imgTableName
                ." SET browse = browse + 1  WHERE eid = '{$eid}' ";
        $db_rs = DB::Query($update_sql);
        return $db_rs ? true : false;
    }
    /*
     * 进入详情页增加一次浏览量--登录用户
     */
    public static function updateBrowseTraceLogin($params=[]){
        $eid = $params['eid'] ? $params['eid'] : '';
        $username = $params['username'] ? $params['username'] : '';
        if( !$eid || !$username ){
            return false;
        }
        //获取图片id
        $params = [
            "eid" => $eid,
        ];
        $img_info = QImgSearch::getOneImg($params);
        if( !$img_info ){
            return false;
        }
        $img_id = $img_info['id'];
        
        //判断当前是否有浏览记录
        $browse_sql = "SELECT id FROM ". QImgSearch::$browseTableName
                ." WHERE img_id = '{$img_id}' AND username = '{$username}' ";
        $browse_rs = DB::GetQueryResult($browse_sql);
        if( $browse_rs ){//更新
            //更新浏览表
            $insert_arr = [
                "update_time" => date("Y-m-d H:i:s"),
            ];
            $db_rs = DB::Update(QImgSearch::$browseTableName, $browse_rs['id'], $insert_arr, 'id');
        }else{
            //更新浏览表
            $insert_arr = [
                "img_id" => $img_id,
                "username" => $username,
                "create_time" => date("Y-m-d H:i:s"),
                "update_time" => date("Y-m-d H:i:s"),
            ];
            $db_rs = DB::Insert(QImgSearch::$browseTableName, $insert_arr, 'id');
        }
        
        return $db_rs ? true : false;
    }
}
