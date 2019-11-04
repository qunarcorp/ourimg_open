<?php

/**
 * 操作流水
 */

class QImgOperateTrace {
    /*
     * 记录图片操作流水
     * 操作类型：submit首次提交审核；reject审核驳回；modify审核后修改；passed审核通过；removed已删除
     */
    public static function recordOperationTrace($params){
        $operate_type = $params['operate_type'] ? $params['operate_type'] : '';//操作类型
        $img_id = $params['img_id'] ? $params['img_id'] : 0;//图片id
        $username = $params['username'] ? $params['username'] : '';//username
        $old_data = $params['old_data'] ? $params['old_data'] : [];//原始数据
        $new_data = $params['new_data'] ? $params['new_data'] : [];//修改后数据
        if( !$operate_type ){
            return [
                'ret' => false,
                'msg' => "operate_type不能为空",
            ];
        }
        
        if( $operate_type == 'modify' ){
            $diff_data = QImgAudit::getUpdateKey($params);
        }
        
        $insert_arr = [
            'operate_type' => $operate_type,
            'img_id' => $img_id,
            'username' => $username,
            'old_data' => $old_data ? json_encode($old_data, true) : null,
            'new_data' => $new_data ? json_encode($new_data, true) : null,
            'diff_data' => $diff_data ? json_encode($diff_data, true) : null,
            'create_time' => date("Y-m-d H:i:s"),
        ];
        $db_rs = DB::Insert(QImgSearch::$auditRecordsTableName, $insert_arr, 'id');
        return [
            'ret' => $db_rs ? true : false,
        ];
    }
    
    
    /*
     * 2019-04-28 增加积分状态
     * 记录图片操作流水
     * 操作类型：submit首次提交审核；reject审核驳回；modify审核后修改；passed审核通过；removed已删除
     */
    public static function recordOperationTracePoint($params){
        $operate_type = $params['operate_type'] ? $params['operate_type'] : '';//操作类型
        $img_id = $params['img_id'] ? $params['img_id'] : 0;//图片id
        $username = $params['username'] ? $params['username'] : '';//username
        $old_data = $params['old_data'] ? $params['old_data'] : [];//原始数据
        $new_data = $params['new_data'] ? $params['new_data'] : [];//修改后数据
        if( !$operate_type ){
            return [
                'ret' => false,
                'msg' => "operate_type不能为空",
            ];
        }
        
        if( $operate_type == 'modify' ){
            $diff_data = QImgAudit::getUpdateKey($params);
        }
        
        $insert_arr = [
            'operate_type' => $operate_type,
            'img_id' => $img_id,
            'username' => $username,
            'old_data' => $old_data ? json_encode($old_data, true) : null,
            'new_data' => $new_data ? json_encode($new_data, true) : null,
            'diff_data' => $diff_data ? json_encode($diff_data, true) : null,
            'create_time' => date("Y-m-d H:i:s"),
            'point_state' => 'pending',
        ];
        $db_rs = DB::Insert(QImgSearch::$auditRecordsTableName, $insert_arr, 'id');
        return [
            'ret' => $db_rs ? true : false,
        ];
    }
    
    /*
     * 查询audit_trace操作流水表--最新的一条审核记录
     */
    public static function getOneAuditTrace($params=[]){
        $img_id = $params['img_id'] ? $params['img_id'] : 0;
        if( !$img_id ){
            return [
                'ret' => false,
                'msg' => '图片信息有误，请重试',
            ];
        }
        
        $select_sql = " SELECT * FROM ".QImgSearch::$auditRecordsTableName;
        
        //img_id
        $select_sql.= " WHERE img_id = '{$img_id}' ";
        $select_sql.= " AND operate_type IN ('reject', 'passed') ";
        $select_sql.= " ORDER BY create_time DESC ";
        
        $select_rs = DB::GetQueryResult($select_sql);
        
        return $select_rs ? $select_rs : [];
    }
    
    /*
     * 获取操作流水--根据图片eid
     */
    public static function getOperateTraces($params=[]){
        $eid = $params['eid'] ? $params['eid'] : 0;
        if( !$eid ){
            return [
                'ret' => false,
                'msg' => '参数有误，图片eid不能为空，请重试',
            ];
        }
        
        //查询图片信息
        $img_info = QImgSearch::getOneImg($params);
        if( !$img_info ){
            return [
                'ret' => false,
                'msg' => '图片信息有误，请重试',
            ];
        }
        
        $img_id = $img_info['id'];
        $select_sql = " SELECT a.*, u.name FROM ".QImgSearch::$auditRecordsTableName
                ." a LEFT JOIN ".QImgSearch::$userTableName." u ON a.username = u.username ";
        
        //img_id
        $select_sql.= " WHERE a.img_id = '{$img_id}' ";
        $select_sql.= " ORDER BY a.create_time ASC ";
        
        $select_rs = DB::GetQueryResult($select_sql, false);
        
        return [
            'ret' => true,
            'data' => $select_rs ? $select_rs : [],
        ];
    }
    
    /*
     * 处理操作流水数据
     */
    public static function dealOperateTraces($params=[]){
        global $dic_img;
        if( !$params || !is_array($params) ){
            return [
                'ret' => false,
                'msg' => '数据有误，请重试',
            ];
        }

        $rs = [];
        foreach( $params as $k => $v ){
            $operate_info = [];
            unset($reject_info);
            $show_operate_time = date("Y-m-d H:i:s", strtotime($v['create_time']));
            if( $v['operate_type'] == 'submit' ){//首次提交
                $show_operate_title = '提交审核';
                $show_operate_desc = $v['name']." 上传素材提交审核";
            }elseif( $v['operate_type'] == 'reject' ){//审核驳回
                $reject_info = json_decode($v['reject_info'], true);
                $show_operate_title = '审核驳回';
                $reason = $reject_info['reason'];
                if( $reason ){
                    $reason_str = "理由：" . implode("，", $reason);
                }
                $name = $v['name'] ? $v['name'] : $v['username'];//系统审核
                $show_operate_desc = $name." 驳回素材 ".$reason_str;
            }elseif( $v['operate_type'] == 'modify' ){//审核后修改
                $diff_data = json_decode($v['diff_data'], true);
                $diff_field_arr = [];
                foreach( $diff_data as $kk => $vv ){
                    $diff_field_arr[] = QImgAudit::$field_value[$vv];
                }
                $diff_field_str = implode('、', $diff_field_arr);
                $show_operate_title = '素材修改';
                $diff_field_str = $diff_field_str ? $diff_field_str : "没有修改新的内容";
                $show_operate_desc = $v['name']." 修改 ".$diff_field_str;
            }elseif( $v['operate_type'] == 'passed' ){//审核通过
                $show_operate_title = '审核通过';
                $show_operate_desc = $v['name']." 审核通过素材";
            }elseif( $v['operate_type'] == 'remove' ){//审核后删除
                $show_operate_title = '已下架';
                $show_operate_desc = $v['name']." 操作下架素材";
            }elseif( $v['operate_type'] == 'soft_remove' ){//审核后删除--增加软删除
                $show_operate_title = '已下架';
                $show_operate_desc = $v['name']." 操作下架素材";
            }elseif( $v['operate_type'] == 'recommend' ){//推荐精选
                $show_operate_title = '评选“精选”';
                $show_operate_desc = $v['name']." 评选素材为“精选推荐”";
            }elseif( $v['operate_type'] == 'unrecommend' ){//取消精选
                $show_operate_title = '取消“精选”';
                $show_operate_desc = $v['name']." 取消素材为“精选推荐”";
            }elseif( $v['operate_type'] == 'system_modify' ){//系统修改
                $show_operate_title = '系统修改';
                $show_operate_desc = "系统修改";
            }
            
            //获取最后一个元素
            if( $rs ){
                $end_value = array_pop($rs);
                if( $v['operate_type'] == 'modify' && $end_value['title'] == '素材修改' ){
                    $operate_info = $end_value['operate_info'];
                }else{//不是审核通过，再放回去
                    array_push($rs, $end_value);
                }
                
                //如果是初次提交后修改，修改后不展示
                if( $v['operate_type'] == 'modify' && $end_value['title'] == '提交审核' ){
                    continue;
                }
            }
            
            $operate_info[] = [
                'desc' => $show_operate_desc,
                'detail' => $reject_info['desc'],
                'time' => $show_operate_time,
            ];
            $rs[] = [
                'title' => $show_operate_title,
                'operate_info' => $operate_info,
            ];
        }
        return $rs;
    }
}
