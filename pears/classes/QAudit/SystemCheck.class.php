<?php

/**
 * 系统审核
 */

class QAudit_SystemCheck {
    
    /*
     * 审核驳回
     */
    public static function reject($params=[]){
        global $dic_img;
        $reason = $dic_img['reject_reason'][8];
        
        //执行事务
        DB::TransBegin();
        
        //更新图片表
        $current_time = date("Y-m-d H:i:s");
        $update_sql = " UPDATE ".QImgSearch::$imgTableName
            ." SET 
            audit_state = 3
            , audit_user = '系统审核'
            , reject_reason = array_append(reject_reason, '{$reason}')
            , audit_time = '{$current_time}'
            , update_time = '{$current_time}'
            WHERE id = '{$params['img_id']}' ";
        $update_rs = DB::Query($update_sql);
        
        //插入流水表
        $reject_reason_arr = [
            'reason' => [
                $reason,
            ],
        ];
        $insert_arr = [
            'img_id' => $params['img_id'],
            'username' => '系统审核',
            'operate_type' => 'reject',
            'create_time' => date("Y-m-d H:i:s"),
            'old_data' => json_encode($params['img_info'], true),
            'reject_info' => json_encode($reject_reason_arr, true),
        ];
        $insert_rs = DB::Insert(QImgSearch::$auditRecordsTableName, $insert_arr, 'id');
        
        //发送消息
        if( $insert_rs && $update_rs ){
            $message = "您贡献的素材{$params['img_info']['title']}，被驳回了，驳回原因：{$reason}！";
            QImgMessage::addAuditMessage ($params['img_info']['username'],$message,'3');
        }
        
        DB::TransCommit();
               
        QLog::info(QImgOperate::$log_prefer, 'img_audit_reject_system', "审核驳回：图片信息:". var_export($params['img_info'],true).";操作人:系统审核");

        return [
            'ret' => $insert_rs && $update_rs ? true : false,
        ];
    }
    
}
