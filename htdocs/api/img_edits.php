<?php

/**
 * 批时不时编辑图片
 */
require_once __DIR__."/../app_api.php";

session_write_close();//关闭session
$json = array("status"=>1,"message"=>"","data"=>[]);

$params = json_decode(file_get_contents("php://input"),true);

$callback = $params['callback'];
//2019-01-10增加保存提交到后端操作，不需要更新audit_state状态，仍然=0
$operate_type = $params['operate_type'];

if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

$action = $params['action'];

//校验必须是提交的
if("edit" != $params['action']){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    $json['message_ext'] = "action";
    display_json_str_common($json,$callback);
}

if(empty($params['imgs'])){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    $json['message_ext'] = "imgs";
    display_json_str_common($json,$callback);
}
define("EDIT_IMG",1);

$sight_infos = array();
$img_infos   = array();

$update_result = array();
$update_result['fail'] = array();
$update_result['fail_title'] = array();
$update_result['ok'] = array();
$update_result['nochange_eid'] = array();
$update_result['nochange_title'] = array();

//校验参数
foreach($params['imgs'] as $key => $current){
    $img_info = QImgSearch::getOneImg(array("eid"=>$current['eid']));
//审核或未审核的图片支持重新编辑，再次进入未审核列表。 在没有审核前可不要审核列表
    $img_infos[$current['eid']] = $img_info;
//只有待提交的才会在批量提交时用到
    if(!$img_info || $img_info['is_del'] =='t' || $img_info['username']!=$login_user_name){
        //不存在，或者图片的用户名不是当前等录的用户
        $json['status'] = 101;
        $json['message'] = "图片不存在";
        $json['message_ext'] = "eid";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }
    $current['title'] = trim($current['title']);
    $current['place'] = trim($current['place']);

    if(empty($current['title'])){

        $json['status'] = 103;
        $json['message'] = "标题必填";
        $json['message_ext'] = "title";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }
    if(!isset($dic_img['purpose'][$current['purpose']])){
        $json['status'] = 103;
        $json['message'] = "用途必选";
        $json['message_ext'] = "purpose";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }
    $current['small_type'] = array_values(array_filter(array_unique((array) $current['small_type']), function ($item) use ($dic_img) {
        return array_key_exists($item, $dic_img['small_type']);
    }));
    if(empty($current['small_type'])){
        $json['status'] = 103;
        $json['message'] = "分类必选";
        $json['message_ext'] = "small_type";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }

    // 图片分类=人物/人像、动物/生物、静物/物品、体育运动、天气气象,拍摄地点选填
    if(empty($current['place']) && empty(array_intersect($current['small_type'], [6,7,8,11,13]))){
        $json['status'] = 103;
        $json['message'] = "地点必填";
        $json['message_ext'] = "place";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }

    if(!is_array($current['keyword'])){
        $json['status'] = 103;
        $json['message'] = "关键词必填";
        $json['message_ext'] = "keyword";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }

    foreach($current['keyword'] as $k=>$v){
        $current['keyword'][$k] = trim($v);
        if(empty($current['keyword'][$k])){
            unset($current['keyword'][$k]);
        }
    }
    $current['keyword'] = array_unique($current['keyword']);
    if(empty($current['keyword'])){
        $json['status'] = 103;
        $json['message'] = "关键词必填";
        $json['message_ext'] = "keyword";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }

    if(is_numeric($current['city_id']) && $current['city_id']>0){

        if(!isset($sight_infos[$current['city_id']])){
            $sight_info = QSight::sight_info($current['city_id']);
            if(empty($sight_info)){
                $json['status'] = 103;
                $json['message'] = "获取城市信息失败，请重试";
                $json['message_ext'] = "city_id";
                $json['message_eid'] = $current['eid'];
                display_json_str_common($json,$callback);
            }else{
                $sight_infos[$current['city_id']] = $sight_info;
            }
        }
    }else{
        //不存在
        $current['city_id'] = 0;
        $sight_info = array();
    }

    if ($current['upload_source_type'] == 'department') {
        $current['upload_source'] = $current['bind_upload_source'];
    }else{
        $current['upload_source'] = '';
    }
    $current["is_signature"] = $current["is_signature"] ? 't' : 'f';

    if (QAuth::isAdmin() || QAuth::isSuperAdmin()) {
        if(mb_strlen($current['original_author']) > 64){
            $json['status'] = 103;
            $json['message'] = "原始作者不能超过64个字";
            $json['message_ext'] = "original_author";
            $json['message_eid'] = $current['eid'];
            display_json_str_common($json,$callback);
        }

        if(mb_strlen($current['purchase_source']) > 64) {
            $json['status'] = 103;
            $json['message'] = "采购来源不能超过64个字";
            $json['message_ext'] = "purchase_source";
            $json['message_eid'] = $current['eid'];
            display_json_str_common($json, $callback);
        }

        if(mb_strlen($current['upload_source']) > 64) {
            $json['status'] = 103;
            $json['message'] = "上传来源不能超过64个字";
            $json['message_ext'] = "upload_source";
            $json['message_eid'] = $current['eid'];
            display_json_str_common($json, $callback);
        }

    }else{
        $current['purchase_source'] = '';
        $current['original_author'] = '';
        $current['upload_source'] = '';
        $current['bind_upload_source'] = '';
        $current['upload_source_type'] = 'personal';
        $current['is_signature'] = 'f';
    }
    
    $params['imgs'][$key] = $current;
    
    //再次编辑保存，判断页面是否有修改内容
    if( $operate_type == 'save' ){
        if( $current['title'] != $img_info['title'] ){
            continue;
        }
        if( $current['place'] != $img_info['place'] ){
            continue;
        }
        if(! empty(array_diff($current['small_type'], json_decode($img_info['small_type'])))
            || ! empty(array_diff(json_decode($img_info['small_type']), $current['small_type']))){
            continue;
        }
        
        $keyword_arr = json_decode($img_info['keyword_arr'], true);
        
        if( array_diff($keyword_arr,$current['keyword']) || array_diff($current['keyword'], $keyword_arr) ){
            continue;
        }
        if( $current['purpose'] != $img_info['purpose'] ){
            continue;
        }
        if( $current['upload_source'] != $img_info['upload_source'] ){
            continue;
        }
        if( $current['is_signature'] != $img_info['is_signature'] ){
            continue;
        }
        if( $current['original_author'] != $img_info['original_author'] ){
            continue;
        }
        if( $current['purchase_source'] != $img_info['purchase_source'] ){
            continue;
        }
        
        $update_result['nochange_title'][] = $current['title'];
        $update_result['nochange_eid'][] = $current['eid'];
        unset($params['imgs'][$key]);
        
    }
    
}
//status=108
//data['fail']=[1,2,3]
//data['ok']=[1,2,3]

//x个素材上传完成啦，Y等几个素材上传失败，需重新提交！

//获取操作记录，判断图片是首次提交还是再次更新
$img_id_arr = array_column($img_infos, 'id');
$img_id_arr_rs = QImgAudit::getImgDistinct(['id'=>$img_id_arr]);

//判断用户最高权限--管理员和超级管理员可以使用传参，其他普通用户只能选择商业用途
$userinfo_rs = QImgPersonal::getUserInfo(['username'=> $login_user_name]);
if(!array_intersect($userinfo_rs['role'], ['admin','super_admin'])){
    $current_purpose = 2;
}

//操作更新
foreach($params['imgs'] as $key => $current){

    $img_info = $img_infos[$current['eid']];

    $sight_info = $sight_infos[$current['city_id']];
    $sight_ltree = QSight::sight_city_json($sight_info);

    DB::TransBegin();
    $update = array();
    $update['update_time'] = date("Y-m-d H:i:s");
    $update['title']    = $current['title'];
    $update['location'] = $sight_ltree ;//ltree 国家 格式 中国.河北省.保定市.涿州市
    $update['city_id'] = $current['city_id'];//城市sight_id
    $update['place'] = $current['place'];//
    $update['small_type'] = json_encode(array_values(array_map(function($smallType){
        return (string) $smallType;
    }, (array) $current['small_type'])));//大分类
    
    $update['purpose'] = $current_purpose ? $current_purpose : $current['purpose'];//用途
    
    $system_check_arr = json_decode($img_info['system_check'], true);
    $system_check_arr['word'] = 0;
    $update['system_check'] = json_encode($system_check_arr, true);//系统审核标记
    if( $operate_type != 'save' ){//新增的保存操作，只保存图片信息，不更新审核状态
        $update['audit_state'] = 1;//审核状态 待审核  0--待提交,1 待审核,--2审核通过,3--审核驳回
    }else{
        $update['audit_state'] = 0;//审核状态 待审核  0--待提交,1 待审核,--2审核通过,3--审核驳回
    }
    $update['audit_time'] = date("Y-m-d H:i:s");
    $update['keyword'] = "{".implode(",",$current['keyword'])."}";

    $update['upload_source'] = $current['upload_source'];
    $update['is_signature'] = $current['is_signature'];
    $update['original_author'] = $current['original_author'];
    $update['purchase_source'] = $current['purchase_source'];

    $rs = DB::Update("public.img",$img_info['id'],$update,"id");
    if(!$rs){
        DB::TransRollback();
        $update_result['fail_title'][] = $current['title'];
        $update_result['fail'][] =$current['eid'];
        continue;
    }

    $extend = array();
    $extend["sight_info"] = $sight_info;
    $extend_json = json_encode($extend,JSON_UNESCAPED_UNICODE);

    $sql = "update public.img_ext set extend=extend||'{$extend_json}' where img_id='{$img_info['id']}'";

    if(!DB::Query($sql)){
        DB::TransRollback();
        $update_result['fail_title'][] = $current['title'];
        $update_result['fail'][] =$current['eid'];
        continue;
    }

    QImgKeyword::push($current['keyword']);
    
    /*
     * 记录操作流水：审核后编辑提交modify
     * 审核后编辑修改提交，记录diff的字段key
     */
    if( in_array($img_info['id'], $img_id_arr_rs) ){//审核后编辑保存
        $operate_type = 'modify';
    }elseif( $operate_type != 'save' ){//提交审核，不是保存的逻辑
        $operate_type = 'submit';
    }
    
    if( $operate_type && $operate_type != 'save' ){
        
        //计算积分
        if( $operate_type == 'submit' ){
            //提交审核单独处理，因为要在外部计算任务积分
            $params_record = [
                'img_id' => $img_info['id'],
                'old_data' => $img_info,
                'new_data' => array_merge($update,['keyword_arr'=>$current['keyword']]),
                'img_id' => $img_info['id'],
                'operate_type' => $operate_type,
                'username' => $login_user_name,
            ];

            $record_rs = QImgOperateTrace::recordOperationTracePoint($params_record);
        }else{
            //其他操作保持不变
            $params_point = [
                'username' => $img_info['username'],//积分用户
                'operate_username' => $login_user_name,//操作用户
                'img_id' => $img_info['id'],//操作图片id
                'audit_state' => $img_info['audit_state'],//操作图片当前状态：本次操作原状态为审核通过，要扣分
                'operate_type' => 'edit',//操作图片类型
            ];
            $task_rs = QImgPoints::pointsDeal($params_point);
            if( !$task_rs ){
                DB::TransRollback();
                $update_result['fail_title'][] = $current['title'];
                $update_result['fail'][] =$current['eid'];
                continue;
            }
            //要先计算积分，不然插入的操作记录会影响积分计算

            $params_record = [
                'img_id' => $img_info['id'],
                'old_data' => $img_info,
                'new_data' => array_merge($update,['keyword_arr'=>$current['keyword']]),
                'img_id' => $img_info['id'],
                'operate_type' => $operate_type,
                'username' => $login_user_name,
            ];

            $record_rs = QImgOperateTrace::recordOperationTrace($params_record);
        }
        
        if( !$record_rs || !$record_rs['ret'] ){
            DB::TransRollback();
            $update_result['fail_title'][] = $current['title'];
            $update_result['fail'][] =$current['eid'];
            continue;
        }

    }
    
    $update_result['ok'][] =$current['eid'];
    
    DB::TransCommit();
}

$fail_count = count($update_result['fail']);
$nochange_count = count($update_result['nochange_eid']);
if($fail_count>0 || $nochange_count>0){

    $message = "";
    $success_count = count($update_result['ok']);
    if($success_count > 0){
        $message .= $success_count."个素材上传完成啦，";
    }

    if( $fail_count > 0 ){
        $message .= implode("、",array_slice($update_result['fail_title'],0,2));
        if( $fail_count > 2 ){
            $message .= " 等".$fail_count."个素材";
        }
        $message .= "上传失败，需重新提交！";
    }
    
    if( $nochange_count > 0 ){
        $nochange_titles = implode("、", $update_result['nochange_title']);
        if( $message ){
            $message.= "；";
        }
        $message .= "以下图片未检测出有修改内容：".$nochange_titles;
    }
    
    $json['status'] = 108;
    $json['message'] = $message;
    $json['data'] = $update_result;
    display_json_str_common($json,$callback);
}else{
    $json['status'] = 0;
    $json['message'] = count($update_result['ok'])."个素材上传完成，棒棒哒！";
    $json['data'] = $update_result;
    display_json_str_common($json,$callback);
}
