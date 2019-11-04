<?php

/**
 * 积分细则
 */

class QPointRule {
    public static $log_prefer = 'img_point';
    public static $log_action = 'rules';
    
    
    /*
     * 保存积分规则
     */
    
    public static function savePointRules($params=[]){
        
        global $login_user_name;
        
        foreach( $params['point_questions'] as $k => $v ){
            if( mb_strlen($v['question']) > 20 || mb_strlen($v['question']) < 1 ){
                return [
                    'status' => 1017,
                    'message' => '问题题目字数在1-20之间',
                ];
            }
        }
        
        $point_obtain_rule = $params['point_obtain_rule'] ? $params['point_obtain_rule'] : '';
        $point_related_instructions = $params['point_related_instructions'] ? $params['point_related_instructions'] : '';
        if( mb_strlen($point_obtain_rule) > 150 ){
            return [
                'status' => 1017,
                'message' => '积分获取规则不能超过150个字',
            ];
        }
        if( mb_strlen($point_related_instructions) > 150 ){
            return [
                'status' => 1017,
                'message' => '规则相关说明不能超过150个字',
            ];
        }


        //验证当前是否已经存在规则记录
        $point_questions = json_encode($params['point_questions'], JSON_UNESCAPED_UNICODE);
        $update_sql = " INSERT INTO "
                . QImgSearch::$pointRulesTableName
                ." (
                      id
                    , point_obtain_rule
                    , point_related_instructions
                    , point_questions
                    , username
                    , update_time
                    , create_time ) 
                    VALUES ( 
                      1,
                      '{$point_obtain_rule}'
                    , '{$point_related_instructions}'
                    , '{$point_questions}'
                    , '{$login_user_name}'
                    , now()
                    , now()
                    ) 
                    ON CONFLICT (id) 
                    DO UPDATE SET  
                      point_obtain_rule = '{$params['point_obtain_rule']}' 
                    , point_related_instructions = '{$params['point_related_instructions']}'
                    , point_questions = '{$point_questions}'
                    , username = '{$login_user_name}'
                    , update_time = now()
                      RETURNING id 
                    ";
        
        QLog::info(self::$log_prefer,self::$log_action,"用户{$login_user_name}更新积分规则sql如下：". $update_sql);
        $update_rs = DB::Query($update_sql);
        $update_rs_row = pg_fetch_row($update_rs);
        $update_id = $update_rs_row[0];
        
        if( !$update_id ){
            return [
                'status' => 1018,
                'message' => '更新失败，请重试',
            ];
        }
        
        return [
            'status' => 0,
            'message' => '更新成功',
        ];

    }
    
    /*
     * 查询积分规则
     */
    
    public static function getPointRules($params=[]){
        
        $rules_sql = " SELECT * FROM ".QImgSearch::$pointRulesTableName;
        $rules_rs = DB::GetQueryResult($rules_sql);
        
        return $rules_rs ? $rules_rs : [];
        
    }
    
}
