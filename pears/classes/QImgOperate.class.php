<?php

/**
 * 图片操作：删除等
 */

class QImgOperate
{

    /**
     * 草稿状态
     */
    const IMG_DRAFT_STATUS = 0;

    /**
     * 审核中
     */
    const IMG_AUDIT_STATUS = 1;

    /**
     * 已通过
     */
    const IMG_PASSED_STATUS = 2;

    /**
     * 已驳回
     */
    const IMG_REJECT_STATUS = 3;


    /**
     * 已下架
     */
    const IMG_SOLD_OUT_STATUS = 4;

    public static $log_prefer = 'img_operate';//日志目录

    /**
     * @param $eIds
     * @param $username
     * @param bool $soft
     * @return bool
     * @throws \QImgApiException
     */
    public static function del($eIds, $username, $soft = true){
        //获取图片信息
        $imgList = QImgSearch::getImgsByeid(['eid' => array_unique((array) $eIds)]);
        //过滤已删除的图片
        $imgList = array_filter($imgList, function($img) use ($username, $soft) {
            return (QAuth::isSuperAdmin($username) && ! $soft) ? true : $img['is_del'] == 'f';
        });
        if (empty($imgList)) {
            throw new \QImgApiException("图片不存在，请重试");
        }
        // 判断是否拥有删除权限
        if (! QAuth::isSuperAdmin($username)) {
            if (! $soft){
                throw new \QImgApiException("删除失败");
            }
            if(! empty(array_filter($imgList, function ($img) use ($username) {
                return $img['username'] != $username ;
            }))){
                throw new \QImgApiException("只能删除自己上传的图片");
            }
        }

        DB::TransBegin();
        QLog::info(self::$log_prefer, $soft ? 'img_soft_del' : 'img_del', var_export($imgList, true));

        $delImgIds = array_column($imgList, 'id');
        $originImgData = array_map(function($imgInfo){
            return array_except($imgInfo, "keyword_arr");
        }, $imgList);
        if (! self::imgDelete($delImgIds, $username, $imgList, $soft)
            || ! self::addOperationTrace($originImgData, $username, $soft ? 'soft_remove' : 'remove')
            || (! $soft && ! self::addDelRecord($delImgIds))
        ){
            DB::TransRollback();
            throw new \QImgApiException("删除失败");
        }
        
        DB::TransCommit();
        return true;
    }

    /**
     * 图片置为删除状态
     * @param $imgIds
     * @param $username
     * @return bool
     */
    private static function imgDelete($imgIds, $username, $imgList, $soft)
    {
        if (empty($imgIds)) {
            return false;
        }
        
        if( $soft ){//管理员清理下架图片，不再进行删除积分操作
            foreach( $imgList as $k => $v ){
                //计算积分
                $params_point = [
                    'username' => $v['username'],//积分用户
                    'operate_username' => $username,//操作用户
                    'img_id' => $v['id'],//操作图片id
                    'operate_type' => 'delete',//操作图片类型
                ];
                $task_rs = QImgPoints::pointsDeal($params_point);
                if( !$task_rs ){
                    return false;
                }
            }
        }

        // 已通过图片置为已下架，其他状态不变
        $updateSql = <<<SQL
  update %s 
  set del_user = '{$username}', is_del = 't', del_time = current_timestamp, update_time = current_timestamp, 
  audit_state = case when audit_state = %u then %u else audit_state end , audit_user = '{$username}'
  where id in (%s)
SQL;
        return !! pg_affected_rows(DB::Query(sprintf(
            $updateSql,
            QImgSearch::$imgTableName,
            self::IMG_PASSED_STATUS,
            self::IMG_SOLD_OUT_STATUS,
            in_where_sql($imgIds)
        )));
    }

    /**
     * 增加图片操作流水
     * @param $operationImgData
     * @param $username
     * @param $operate
     * @return bool
     */
    private static function addOperationTrace($originImgData, $username, $operate)
    {
        foreach ($originImgData as $imgInfo){
            $insertSql = <<<SQL
  INSERT INTO %s (img_id, username, operate_type, old_data, diff_data, create_time)
  VALUES ('{$imgInfo["id"]}', '{$username}', '{$operate}', '%s', '%s', current_timestamp)
SQL;
            $currentImgInfo = (array) DB::GetQueryResult("select * from public.img where id = '{$imgInfo["id"]}'");
            $executeResult = !! pg_affected_rows(\DB::Query(sprintf(
                $insertSql,
                QImgSearch::$auditRecordsTableName,
                DB::EscapeString(json_encode($imgInfo, JSON_UNESCAPED_UNICODE)),
                DB::EscapeString(json_encode(db_row_diff($currentImgInfo, $imgInfo), JSON_UNESCAPED_UNICODE))
            )));
            if (! $executeResult) {
                return false;
            }
        }


        return true;
    }

    /**
     * 添加图片删除记录 移动图片到新的存储位置
     * @param $imgIds
     * @return bool
     */
    private static function addDelRecord($imgIds)
    {
        $insertSql = <<<SQL
  INSERT INTO %s (url, ceph_del)
  SELECT url, 'f' FROM %s WHERE id in (%s)
  ON CONFLICT (url)
  DO UPDATE SET update_time=current_timestamp 
SQL;

        return !! pg_affected_rows(DB::Query(sprintf(
            $insertSql,
            QImgSearch::$imgDelRecordTableName,
            QImgSearch::$imgTableName,
            in_where_sql($imgIds)
        )));
    }
    
    /*
     * 图片删除-单个操作
     */
    public static function imgDel($params=[]){
        $eid = $params['eid'] ? $params['eid'] : '';//eid
        $username = $params['username'] ? $params['username'] : '';//username
        $audit_state = $params['audit_state'] ? $params['audit_state'] : '';//username

        //查询eid对应的图片
        $params = [
            'eid' => $eid,
        ];
        $img_info = QImgSearch::getOneImg($params);
        if( !$img_info ){
            return [
                'ret' => false,
                'msg' => '图片不存在，请重试',
            ];
        }
        
        if( $img_info['username'] != $username ){
            return [
                'ret' => false,
                'msg' => '只能删除自己上传的图片',
            ];
        }
        if($audit_state!='' && $img_info['audit_state'] !=$audit_state){
            return [
                'ret' => false,
                'msg' => '图片信息有误，请重试',
            ];
        }

        DB::TransBegin();
        $cond_arr = [
            'eid' => $eid,
            'del_user' => $username,
            'is_del' => 't',
        ];
        if(2==$img_info['audit_state']){
            //审核通过的。需要改成已下架状态
            $cond_arr['audit_state'] = 4;
        }
        //柱哥-上传处提交，必须是待提交状态
        if($audit_state!=''){
            $cond_arr["audit_state"] = "0";
        }
        QLog::info(self::$log_prefer, 'img_del', var_export($img_info, true));
        $db_rs = DB::Update(QImgSearch::$imgTableName, $img_info['id'], $cond_arr);
        if($db_rs){

            $sql = "insert into ".QImgSearch::$imgDelRecordTableName." 
            (url,ceph_del) VALUES('{$img_info['url']}','f')
            ON CONFLICT (url) 
             DO UPDATE SET ceph_del='f',update_time=current_timestamp 
             ";
            $db_rs = DB::Query($sql);
            if($db_rs){
                /*
                * 记录操作流水：用户删除remove
                */
               $params_record = [
                   'img_id' => $img_info['id'],
                   'operate_type' => 'remove',
                   'username' => $username,
               ];
               $record_rs = QImgOperateTrace::recordOperationTrace($params_record);
               if( !$record_rs || !$record_rs['ret'] ){
                   DB::TransRollback();
               }else{
                   DB::TransCommit();
               }
            }else{
                DB::TransRollback();
            }
        }else{
            DB::TransRollback();
        }

        return [
            'ret' => $db_rs ? true : false,
        ];
    }
    
    /*
     * 图片删除--批量操作
     */
    public static function imgDelBatch($params=[]){
        $eid = $params['eid'] ? $params['eid'] : [];//eid
        $username = $params['username'] ? $params['username'] : '';//username
        
        $eid_str = implode("','", $eid);
        $sql_where = "  WHERE eid IN ('{$eid_str}') AND username = '{$username}' ";
        
        //插入队列表中--脚本获取数据判断服务器的图片路径是否需要删除
        $select_sql = "SELECT url ,'f' FROM ". QImgSearch::$imgTableName . $sql_where;
        $insert_sql = " INSERT INTO ". QImgSearch::$imgDelRecordTableName
            . " (url,ceph_del) $select_sql
            ON CONFLICT (url) 
             DO UPDATE SET ceph_del='f',update_time=current_timestamp ";
        
        //更新图片表
        $update_sql = " UPDATE ". QImgSearch::$imgTableName." SET is_del = 't', username = '{$username}' ".$sql_where . " and audit_state!=2";
        //更新图片表下线
        $update_sql_down = " UPDATE ". QImgSearch::$imgTableName." SET is_del = 't',audit_state=4, username = '{$username}' ".$sql_where . " and audit_state=2";

        //插入更新流水表--记录操作流水：用户删除remove
        $select_trace_sql = " SELECT id, '{$username}', 'remove', current_timestamp FROM ".QImgSearch::$imgTableName . $sql_where;
        $insert_trace_sql = " INSERT INTO ".QImgSearch::$auditRecordsTableName
                ." (img_id,username,operate_type,create_time) $select_trace_sql ";
        
        QLog::info(self::$log_prefer, 'img_del_batch', "批量删除操作：eid:". var_export($eid,true).";username:".$username);
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
    
    /*
     * 搜索时记录关键词搜索信息
     * username 用户名 string 可为空-用户未登录
     * 其他的搜索信息全部存下来
     */
    public static function updateSearchs($params=[]){
        $insert_arr = [
            'extend' => json_encode($params,JSON_UNESCAPED_UNICODE),
            'username' => $params['username'],
            'create_time' => date("Y-m-d H:i:s"),
        ];
        $db_rs = DB::Insert(QImgSearch::$searchTableName, $insert_arr, 'id');
        return $db_rs ? true : false;
    }
    
    /**
     * 更新下载数
     * @param $id
     * @return bool|resource
     */
    public static function addDownloadNum($id){
        $update_sql = " UPDATE ".QImgSearch::$imgTableName  
                ." SET download = download + 1 WHERE id = '{$id}' ";
        return DB::Query($update_sql);
    }
    
    /*
     * 用户点赞
     */
    public static function userPraise($params=[]){
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
        
        //检查用户是否已经点赞过
        $select_sql = "SELECT id FROM ".QImgSearch::$praiseTableName." WHERE img_id = '{$img_id}' "
        . " AND username = '{$username}' ";
        $favorite_rs = DB::GetQueryResult($select_sql);
        if( $favorite_rs ){
            return [
                'ret' => true,
                'msg' => '您已点赞，不需要重复操作',
            ];
        }
        
        //事务
        DB::TransBegin();
        
        //更新收藏表
        $insert_arr = [
            "img_id" => $img_id,
            "username" => $username,
            "create_time" => date("Y-m-d H:i:s"),
        ];
        $insert_rs = DB::Insert(QImgSearch::$praiseTableName, $insert_arr, 'id');
        if( !$insert_rs ){
            DB::TransRollback();
            return [
                'ret' => false,
                'msg' => '操作有误，请稍后重试',
            ];
        }
        
        //更新图片表
        $update_sql = " UPDATE ".QImgSearch::$imgTableName
            ." SET praise = ( CASE WHEN praise > 0 THEN praise + 1 ELSE 1 END )
                , update_time = now() WHERE id = '{$img_id}' RETURNING id ";
        
        $update_rs = DB::Query($update_sql);
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
            'img_id' => $img_id,//操作图片id
            'operate_type' => 'praise',//操作图片类型
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
    /*
     * 用户取消点赞
     */
    public static function cancelPraise($params=[]){
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
        $select_sql = "SELECT id FROM ".QImgSearch::$praiseTableName." WHERE img_id = '{$img_id}' "
        . " AND username = '{$username}' ";
        $favorite_rs = DB::GetQueryResult($select_sql);
        if( !$favorite_rs ){
            return [
                'ret' => true,
                'msg' => '您还没有点赞过，请前往点赞',
            ];
        }
        
        //更新收藏表
        $del_cond = [
            'id' => $favorite_rs['id'],
            'img_id' => $img_id,
            'username' => $username,
        ];
        $db_rs = DB::Delete(QImgSearch::$praiseTableName, $del_cond);
        
        //更新图片表
        if( $img_info['praise'] && $img_info['praise'] > 0 ){
            $update_sql = " UPDATE ".QImgSearch::$imgTableName
                ." SET praise = praise - 1, update_time = now() WHERE id = '{$img_id}' ";
        }
        DB::Query($update_sql);
        return [
            'ret' => true,
            'msg' => '取消成功',
        ];
    }

    /**
     * star
     * @param $eid
     * @return bool
     */
    public static function star($eid)
    {
        $eidStr = array2insql($eid);

        $sql = <<<SQL
    update public.img set star = 't', star_time = current_timestamp where eid in ({$eidStr})
SQL;

        $updateResult = DB::Query($sql);

        return !! pg_affected_rows($updateResult);
    }

    /**
     * unstar
     * @param $eid
     * @return bool
     */
    public static function unstar($eid)
    {
        $eidStr = array2insql($eid);

        $sql = <<<SQL
    update public.img set star = 'f', star_time = null where eid in ({$eidStr})
SQL;

        $updateResult = DB::Query($sql);

        return !! pg_affected_rows($updateResult);
    }
}
