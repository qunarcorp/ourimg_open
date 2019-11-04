<?php

/**
 * 我的下载
 */

class QImgMyDownload {
    /*
     * 获取我的下载列表
     */
    public static function getMyDownload($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        $keyword = $params['keyword'] ? $params['keyword'] : [];
        $offset = $params['offset'] ? $params['offset'] : '';//偏移量-分页用
        $limit = $params['limit'] ? $params['limit'] : '';//数量限制-分页用
        $sort_by = $params['sort_by'] ? $params['sort_by'] : '';//排序
        unset($params['offset']);
        unset($params['limit']);
        unset($params['sort_by']);
        if( !$username ){
            return false;
        }
        
        $sql = "SELECT i.*, array_to_json(keyword) AS keyword_arr, f.update_time AS operate_time FROM "
                . QImgSearch::$imgTableName." i JOIN "
                .QImgSearch::$downloadTableName." f ON i.id = f.img_id ";

        $params['operate_type'] = 'download';
        $sql.= QImgPersonal::getJoinSqlWhere($params);

        //排序
        $sql.= " ORDER BY f.update_time DESC ";

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
     * 获取我的下载列表数量
     */
    public static function getMyDownloadCount($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        if( !$username ){
            return false;
        }
        
        $sql = "SELECT COUNT(1) FROM ".QImgSearch::$imgTableName." i JOIN "
                .QImgSearch::$downloadTableName." f ON i.id = f.img_id ";
        
        $sql.= QImgPersonal::getJoinSqlWhere($params);
                
        $db_rs = DB::GetQueryResult($sql);
        
        return $db_rs['count'] ? $db_rs['count'] : 0;
    }
    
    /*
     * 用户下载
     */
    public static function userDownload($params=[]){
        $eid = $params['eid'] ? $params['eid'] : 0;
        $username = $params['username'] ? $params['username'] : '';//用户名
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
        
        //更新下载表
        $insert_arr = [
            "img_id" => $img_id,
            "username" => $username,
            "create_time" => date("Y-m-d H:i:s"),
        ];
        DB::Insert(QImgSearch::$downloadTableName, $insert_arr, 'id');
        
        //更新图片表
        if(!$img_info['download']){
            $update_sql = " UPDATE ".QImgSearch::$imgTableName
                ." SET download = 1, update_time = now() WHERE id = '{$img_id}' ";
        }else{
            static ::addDownloadNum($img_id);
        }
        DB::Query($update_sql);
        return true;
    }
}
