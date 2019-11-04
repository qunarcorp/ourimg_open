<?php

/**
 * 审核流程
 */

class QImgAuditOperate {
    /*
     * 审核通过--支持批量
     * 已经审核通过的不处理
     */
    public static function auditPassBatch($params=[]){
        $eid = $params['eid'] ? $params['eid'] : [];//eid
        $username = $params['username'] ? $params['username'] : '';//username审核人
        
        $eid_str = implode("','", $eid);
        $sql_where = "  WHERE eid IN ('{$eid_str}') AND audit_state != '2' ";

        $old_is_star = [];
        //执行事务
        DB::TransBegin();
        
        //查询图片所有信息
        $select_sql = " SELECT *, array_to_json(keyword) as keyword_arr FROM ".QImgSearch::$imgTableName . $sql_where;
        $select_rs = DB::GetQueryResult($select_sql, false);
        if( !$select_rs ){
            return [
                'ret' => false,
                'msg' => '图片信息有更新，请刷新页面重试',
            ];
        }

        //更新图片表
        $update_sql = " UPDATE ". QImgSearch::$imgTableName
                ." SET audit_state = '2', audit_user = '{$username}',  
                 audit_time = current_timestamp, update_time = current_timestamp "
                .$sql_where;
        $update_rs = DB::Query($update_sql);
                
        //插入更新流水表--记录操作流水：审核通过passed
        $insert_trace_sql = " INSERT INTO ".QImgSearch::$auditRecordsTableName
                ." (img_id,username,operate_type,create_time,old_data,new_data,point_state) VALUES  ";
        $insert_trace_sql_tmp = '';
        foreach( $select_rs as $k => $v ){
            $old_data = json_encode($v, true);

            if ($v['star'] =='t'){
                # 旧的是推荐 的需要把积分加回来
                $old_is_star[] = $v['eid'];
            }

            //本次修改新数据
            $new_data = $v;
            $new_data['keyword_arr'] = json_decode($v['keyword_arr'], true);
            $new_data['audit_state'] = 2;
            $new_data['audit_user'] = $username;
            $new_data = json_encode($new_data, JSON_UNESCAPED_UNICODE);
            
            
            if( $insert_trace_sql_tmp ){
                $insert_trace_sql_tmp.= " , ";
            }
            $pointState = ! empty($v["upload_source"]) ? "null" : "'pending'";
            $insert_trace_sql_tmp.= " ({$v['id']}, '{$username}', 'passed', current_timestamp, '{$old_data}', '{$new_data}', {$pointState} ) ";
            //积分计算在脚本计算
        }
        $insert_trace_sql.= $insert_trace_sql_tmp;
        $insert_rs = DB::Query($insert_trace_sql);
        
        //发送消息
        if( $insert_rs && $update_rs ){
            foreach( $select_rs as $k => $v ){
                $message = "您贡献的素材{$v['title']}，被审核通过啦！";
                QImgMessage::addAuditMessage ($v['username'],$message,'2');
            }
        }
        
        DB::TransCommit();
               
        QLog::info(QImgOperate::$log_prefer, 'img_audit_passed_batch', "批量审核通过：eid:". var_export($eid,true).";操作人:".$username);

        return [
            'ret' => $insert_rs && $update_rs ? true : false,
        ];
    }
    
    /*
     * 审核驳回
     */
    public static function auditReject($eid, $username, array $reject_reason)
    {
        //查询图片
        $img_info = QImgSearch::getOneImg(["eid" => $eid]);
        if(! $img_info ) {
            return [
                'ret' => false,
                'msg' => "图片信息有误，请重试",
            ];
        }
        if($img_info['is_del'] == 't' || $img_info['audit_state'] == '4' ) {
            return [
                'ret' => false,
                'msg' => "图片已被删除，不能执行操作",
            ];
        }
        if($img_info['audit_state'] == '3' ) {
            return [
                'ret' => false,
                'msg' => "图片已被驳回，不能重复操作",
            ];
        }

        //执行事务
        DB::TransBegin();
        
        //更新图片表
        $update_arr = [
            'audit_state' => 3,
            'audit_user' => $username,
            'audit_desc' => "",
            'reject_reason' => array2pgarray($reject_reason),
            'audit_time' => date("Y-m-d H:i:s"),
            'update_time' => date("Y-m-d H:i:s"),
        ];
        $update_rs = DB::Update(QImgSearch::$imgTableName, $img_info['id'], $update_arr, 'id');

        //插入流水表
        $reject_reason_arr = [
            'reason' => $reject_reason,
            'desc' => "",
        ];
        $insert_arr = [
            'img_id' => $img_info['id'],
            'username' => $username,
            'operate_type' => 'reject',
            'create_time' => date("Y-m-d H:i:s"),
            'old_data' => json_encode($img_info, true),
            'reject_info' => json_encode($reject_reason_arr, true),
        ];
        $insert_rs = DB::Insert(QImgSearch::$auditRecordsTableName, $insert_arr);
        
        //计算积分
        $params_point = [
            'username' => $img_info['username'],//积分用户
            'operate_username' => $username,//操作用户
            'img_id' => $img_info['id'],//操作图片id
            'operate_type' => 'reject',//操作图片类型
        ];
        $task_rs = QImgPoints::pointsDeal($params_point);
        if( !$task_rs ){
            DB::TransRollback();
            return [
                'ret' => false,
                'msg' => '操作失败：积分计算过程出现问题',
            ];
        }
        
        //发送消息
        global $dic_img;
        if( $insert_rs && $update_rs ){
            $reason = implode("、", $reject_reason);
            $message = "您贡献的素材{$img_info['title']}，被驳回了，驳回原因：{$reason}！";
            QImgMessage::addAuditMessage($img_info['username'],$message,'3');
        }
        
        DB::TransCommit();
               
        QLog::info(QImgOperate::$log_prefer, 'img_audit_reject', "审核驳回：图片信息:". var_export($img_info,true).";操作人:".$username);

        return [
            'ret' => $insert_rs && $update_rs ? true : false,
        ];
    }
    
    /*
     * 图片删除--批量操作
     * 管理员操作
     */
    public static function imgDelBatch($params=[]){
        $eid = $params['eid'] ? $params['eid'] : [];//eid
        $username = $params['username'] ? $params['username'] : '';//username
        
        $eid_str = implode("','", $eid);
        $sql_where = "  WHERE eid IN ('{$eid_str}') ";
        
        //插入队列表中--脚本获取数据判断服务器的图片路径是否需要删除
        $select_sql = "SELECT url ,'f' FROM ". QImgSearch::$imgTableName . $sql_where;
        $insert_sql = " INSERT INTO ". QImgSearch::$imgDelRecordTableName
            . " (url,ceph_del) $select_sql
            ON CONFLICT (url) 
             DO UPDATE SET ceph_del='f',update_time=current_timestamp ";
        
        //更新图片表
        $update_sql = " UPDATE ". QImgSearch::$imgTableName." SET is_del = 't', del_user = '{$username}' ".$sql_where . " and audit_state!=2";
        //更新图片表下线
        $update_sql_down = " UPDATE ". QImgSearch::$imgTableName
                ." SET is_del = 't', del_user = '{$username}',audit_state=4 ".$sql_where . " and audit_state=2";

        //插入更新流水表--记录操作流水：用户删除remove
        $select_trace_sql = " SELECT id, '{$username}', 'remove', current_timestamp FROM ".QImgSearch::$imgTableName 
                . $sql_where;
        $insert_trace_sql = " INSERT INTO ".QImgSearch::$auditRecordsTableName
                ." (img_id,username,operate_type,create_time) $select_trace_sql ";
        
        QLog::info(QImgOperate::$log_prefer, 'img_del_batch_admin', "批量删除操作：eid:". var_export($eid,true).";username:".$username);
        DB::TransBegin();
        $insert_rs = DB::Query($insert_sql);

        $ok = false;
        if($insert_rs){
            $update_rs = DB::Query($update_sql);
            if($update_rs){
                $update_rs_down = DB::Query($update_sql_down);
                if($update_rs_down){
                    $insert_trace_rs = DB::Query($insert_trace_sql);
                    if($insert_trace_rs){
                        $ok = true;
                    }
                }
            }
        }
        if($ok){
            DB::TransCommit();
        }else{
            DB::TransRollback();
        }

        return [
            'ret' => $ok ? true : false,
        ];
    }
}
