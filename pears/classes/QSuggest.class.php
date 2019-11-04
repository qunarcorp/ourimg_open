<?php

/**
 * 搜索记录和suggest
 */

class QSuggest {
    //记录搜索记录
    public static function record($params=[]){
        global $login_user_name;
        $query = $params['query'];
        $field_name = $params['field_name'];
        $source = $params['source'];
        $insert_info = [
            'query' => $query,
            'field_name' => $field_name,
            'source' => $source,
            'username' => $login_user_name,
        ];
        $insert_rs = DB::Insert(QImgSearch::$searchRecordTableName, $insert_info, 'id');
    }
    
    //多个搜索记录
    public static function recordBatch($params=[]){
        $query_arr = $params['query_arr'];
        $source = $params['source'];
        foreach( $query_arr as $k => $v ){
            $record_params = [
                'query' => $v,
                'field_name' => $k,
                'source' => $source,
            ];
            self::record($record_params);
        }
    }
    
    //获取搜索记录
    public static function getSuggest($params=[]){
        global $login_user_name;
        $query = $params['query'];
        $field_name = $params['field_name'];
        $sql = " SELECT * FROM ".QImgSearch::$searchRecordTableName
                ." WHERE username = '{$login_user_name}' AND field_name = '{$field_name}' AND query ~ '{$query}' ORDER BY id DESC LIMIT 10 ";
        $list = DB::GetQueryResult($sql, false);
        return $list;
    }
    
}
