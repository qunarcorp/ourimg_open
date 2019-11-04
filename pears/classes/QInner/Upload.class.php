<?php

/**
 * 内部接口上传
 */

class QInner_Upload {
    
    public static function autoPass($params=[]){
        $pass_info = [
            'img_id' => $params['img_id'],
            'username' => $params['username'],
            'old_data' => json_encode($params['img_info'], true),
            'operate_type' => 'passed',
            'create_time' => $params['create_time'] ? $params['create_time'] : date("Y-m-d H:i:s"),
        ];
        
        $pass_rs = DB::Insert(QImgSearch::$auditRecordsTableName, $pass_info, 'id');
        return $pass_rs;
    }

}
