<?php

/**
 * 发布活动任务类
 */

class QActivity_ReleaseManage {
    
    //定义错误code
    public static $errorCode = [
        0 => '操作成功',
        201 => '更新数据库失败',
        202 => 'eid更新失败',
        203 => '信息有误，没有获取到相应的活动信息',
        204 => '活动已开始，信息不能修改',
        205 => '活动名称填写有误',
        206 => '活动介绍填写有误',
        207 => '活动说明填写有误',
        208 => '活动奖励填写有误',
        209 => '作品要求填写有误',
        210 => '状态有误，请重试',
        211 => '城市景点有误，请重试',
        212 => '开始时间不能早于当前时间，请重试',
        213 => '结束时间不能早于当前时间，请重试',
        214 => '结束时间不能早于开始时间，请重试',
    ];

    //post参数有效性验证
    public static function paramsValidator($params=[]){
        if(mb_strlen($params['activity_title']) < 1||strlen($params['activity_title']) > 40){
            self::display_result(['errorCode'=>205]);
        }
        if(mb_strlen($params['activity_introduction']) > 500){
            self::display_result(['errorCode'=>206]);
        }
        if(mb_strlen($params['activity_description']) > 500){
            self::display_result(['errorCode'=>207]);
        }
        if(mb_strlen($params['activity_reward']) > 500){
            self::display_result(['errorCode'=>208]);
        }
        if(mb_strlen($params['img_requirements']) > 500){
            self::display_result(['errorCode'=>209]);
        }
        //验证是否是json类型
        $city_sights_check = self::checkIsJson($params['city_sights']);
        if( !$city_sights_check ){
            self::display_result(['errorCode'=>211]);
        }
        
        //验证在线时间
        self::checkBeginTime($params);
    }
    
    //判断是否是json类型
    public static function checkIsJson($params){
        $json_rs = json_decode($params, true);
        if( json_last_error() == JSON_ERROR_NONE ){
            return true;
        }else{
            return false;
        }
    }
    
    //结果返回
    public static function display_result($params=[]){
        $rs = [
            'status' => $params['errorCode'],
            'message' => self::$errorCode[$params['errorCode']],
            'data' => $params['data'] ? $params['data'] : '',
        ];
        display_json_str_common($rs, $callback);
    }
    
    //insert活动信息
    public static function insert($params=[]){
        $insert_info = $params['insert_info'];
        $insert_rs = DB::Insert(QImgSearch::$activityTasksTableName, $insert_info, 'id');
        return $insert_rs;
    }
    
    //update活动信息
    public static function update($params=[]){
        $insert_info = $params['insert_info'];
        $eid = $params['eid'];
        $insert_rs = DB::Update(QImgSearch::$activityTasksTableName, $eid, $insert_info, 'eid');
        return $insert_rs;
    }
    
    //生成eid
    public static function getEid($params=[]){
        $id = $params['id'];
        $IDEncipher = new IDEncipher();
        $eid = $IDEncipher->encrypt($id);
        return $eid;
    }
    
    //根据eid查询活动信息
    public static function getActivityInfo($params=[]){
        $eid = $params['eid'];
        $sql = " SELECT * FROM ".QImgSearch::$activityTasksTableName." WHERE eid='{$eid}' ";
        $activity_info = DB::GetQueryResult($sql);
        return $activity_info;
    }
    
    //查询list
    public static function getList($params=[]){
        $activity_title = $params['activity_title'];
        $activity_type = $params['activity_type'];
        $state = $params['state'];
        $offset = abs(intval($params['offset'] ?: 0));
        $pageSize = abs(intval($params['limit'] ?: 10));
        $eid = $params['eid'];
        $valid_begin_time = $params['valid_begin_time'];//是否在有效期范围内 有效期开始时间
        $valid_end_time = $params['valid_end_time'];//是否在有效期范围内 有效期结束时间
        
        //查询结果
        $whereSql = " WHERE 1 = 1 ";
        if( $eid ){
            $whereSql.= " AND eid = '{$eid}' ";
        }
        if( $activity_title ){
            $whereSql.= " AND activity_title ~ '{$activity_title}' ";
        }
        if( $activity_type ){
            $whereSql.= " AND '{$activity_type}' = ANY(activity_type) ";
        }
        if( $state ){
            if( $state == 'end' ){//已结束
                $whereSql.= " AND state = 'online' AND end_time < now() ";
            }elseif( $state == 'online' ){
                $whereSql.= " AND state = '{$state}' AND (end_time > now() OR end_time IS NULL) ";
            }else{
                $whereSql.= " AND state = '{$state}' ";
            }
        }
        if( $valid_begin_time ){
            $whereSql.= " AND begin_time >= '{$valid_begin_time}' ";
        }
        if( $valid_end_time ){
            $whereSql.= " AND end_time <= '{$valid_end_time}' ";
        }
        
        $countSql = " SELECT count(*) as aggregate FROM ".QImgSearch::$activityTasksTableName . $whereSql;

        $total = (int) DB::GetQueryResult($countSql)["aggregate"];

        $listSql = " SELECT * FROM ".QImgSearch::$activityTasksTableName . $whereSql . " ORDER BY update_time DESC  LIMIT {$pageSize} OFFSET {$offset} ";

        $list = DB::GetQueryResult($listSql, false);
        
        //更新搜索记录
        $suggest_info = [
            'query_arr' => [
                'eid' => $eid,
                'activity_title' => $activity_title,
            ],
            'source' => 'activity',
        ];
        QSuggest::recordBatch($suggest_info);
        
        //返回
        return [
            "total" => $total,
            "pageSize" => $pageSize,
            "list" => $list,
        ];
    }
    
    //验证活动状态
    public static function checkState($params=[]){
        $new_state = $params['new_state'];
        $activity_info = self::getActivityInfo(['eid'=>$params['eid']]);
        if( !$activity_info ){
            self::display_result(['errorCode'=>203]);
        }
        
        if( $activity_info['state'] == $new_state ){
            self::display_result(['errorCode'=>210]);
        }
        return $activity_info;
    }
    
    //活动下线
    public static function offline($params=[]){
        global $login_user_name;
        $update_params = [
            'state' => 'offline',
            'update_username' => $login_user_name,
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $update_rs = DB::Update(QImgSearch::$activityTasksTableName, $params['eid'], $update_params, 'eid');
        $errorCode = $update_rs ? 0 : 201;
        self::display_result(['errorCode'=>$errorCode]);
    }
    
    //验证活动开始时间大于等于当前时间
    public static function checkBeginTime($params=[]){
        $begin_time = $params['begin_time'];
        $end_time = $params['end_time'];
        if( !$begin_time && !$end_time ){
            //长期有效，不判断
            return true;
        }
        
        $current_time = date("Y-m-d H:i:s");
        if( $begin_time && $begin_time < $current_time ){
            self::display_result(['errorCode'=>212]);
        }
        if( $end_time && $end_time < $current_time ){
            self::display_result(['errorCode'=>213]);
        }
        if( $begin_time && $end_time && $end_time < $begin_time ){
            self::display_result(['errorCode'=>214]);
        }
    }
    
    //活动发布
    public static function online($params=[]){
        global $login_user_name;
        $update_params = [
            'state' => 'online',
            'update_username' => $login_user_name,
            'release_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $update_rs = DB::Update(QImgSearch::$activityTasksTableName, $params['eid'], $update_params, 'eid');
        $errorCode = $update_rs ? 0 : 201;
        self::display_result(['errorCode'=>$errorCode]);
    }
    
    //处理list数组
    public static function dealList($params=[]){
        $activity_arr = $params['activity_arr'];
        $activity_list = [];
        foreach( $activity_arr as $k => $v ){
            
            //判断状态
            if( $v['state'] == 'online' ){
                if( $v['end_time'] && $v['end_time'] < date("Y-m-d H:i:s") ){
                    $v['state'] = 'end';//活动已结束
                }
            }
            
            $activity_list[] = [
                'eid' => $v['eid'],
                'activity_title' => $v['activity_title'],
                'activity_type' => pgarray2array($v['activity_type']),
                'city_sights' => json_decode($v['city_sights'], true),
                'theme_keywords' => pgarray2array($v['theme_keywords']),
                'img_upload_points' => $v['img_upload_points'] > 0 ? $v['img_upload_points'] : '0',
                'task_points' => $v['task_points'] > 0 ? $v['task_points'] : '0',
                'need_img_count' => $v['need_img_count'] > 0 ? $v['need_img_count'] : '0',
                'now_img_counts' => $v['now_img_counts'] > 0 ? $v['now_img_counts'] : '0',
                'points_cycle' => $v['points_cycle'],
                'points_time_type' => $v['points_time_type'],
                'activity_introduction' => $v['activity_introduction'],
                'activity_description' => $v['activity_description'],
                'activity_reward' => $v['activity_reward'],
                'img_requirements' => $v['img_requirements'],
                'background_img' => $v['background_img'],
                'state' => $v['state'],
                'create_username' => $v['create_username'],
                'update_username' => $v['update_username'],
                'begin_time' => $v['begin_time'] ? date("Y-m-d H:i:s", strtotime($v['begin_time'])) : '',
                'end_time' => $v['end_time'] ? date("Y-m-d H:i:s", strtotime($v['end_time'])) : '',
                'create_time' => $v['create_time'] ? date("Y-m-d H:i:s", strtotime($v['create_time'])) : '',
                'release_time' => $v['release_time'] ? date("Y-m-d H:i:s", strtotime($v['release_time'])) : '',
                'update_time' => $v['update_time'] ? date("Y-m-d H:i:s", strtotime($v['update_time'])) : '',
            ];
        }
        
        return $activity_list ? $activity_list : [];
    }
}
