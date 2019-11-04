<?php
/**
 * 单个编辑图片
 */
require_once __DIR__."/../app_api.php";
session_write_close();//关闭session

$json = array("status"=>1,"message"=>"","data"=>[]);

$params = json_decode(file_get_contents("php://input"),true);


$callback = $params['callback'];

if(!isset($login_user_name) || empty($login_user_name)){
    //未登录
    $json['status'] = 100;
    $json['message'] = "未登录用户不允许操作";
    display_json_str_common($json,$callback);
}

$action = $params['action'];

$eid = $params['eid'];

//参数错误
if(empty($eid) || !is_numeric($eid)){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}


$img_info = QImg::getInfo(array("eid"=>$eid,"is_del"=>"f"));

if(!$img_info || $img_info['username']!=$login_user_name){
    //不存在，或者图片的用户名不是当前等录的用户
    $json['status'] = 101;
    $json['message'] = "图片不存在";
    display_json_str_common($json,$callback);
}


if("edit" == $params['action']){
    $current = $params;
    $img_info = QImgSearch::getOneImg(array("eid"=>$current['eid']));
//审核或未审核的图片支持重新编辑，再次进入未审核列表。 在没有审核前可不要审核列表

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
    
    //再次编辑保存，判断页面是否有修改内容
    $operate_type = $params['operate_type'];
    if( $operate_type == 'save' ){
        if( $current['title'] == $img_info['title'] && $current['place'] == $img_info['place']
            && empty(array_diff($current['small_type'], json_decode($img_info['small_type'], true))) && empty(array_diff(json_decode($img_info['small_type'], true), $current['small_type']))
            && $current['purpose'] == $img_info['purpose'] ){
            $keyword_arr = json_decode($img_info['keyword_arr'], true);
            if( !array_diff($keyword_arr,$current['keyword']) && !array_diff($current['keyword'], $keyword_arr) ){
                $json['status'] = 109;
                $json['message'] = "图片内容没有修改，请修改后重试";
                $json['message_ext'] = "";
                $json['message_eid'] = $current['eid'];
                display_json_str_common($json,$callback);
            }
        }
    }

    if(is_numeric($current['city_id']) && $current['city_id']>0){
        $sight_info = QSight::sight_info($current['city_id']);
        if(empty($sight_info)){
            $json['status'] = 103;
            $json['message'] = "获取城市信息失败";
            $json['message_ext'] = "city_id";
            $json['message_eid'] = $current['eid'];
            display_json_str_common($json,$callback);
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
        if(mb_strlen($current['purchase_source']) > 64){
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

    $sight_ltree = QSight::sight_city_json($sight_info);
    
    //判断用户最高权限--管理员和超级管理员可以使用传参，其他普通用户只能选择商业用途
    $userinfo_rs = QImgPersonal::getUserInfo(['username'=> $login_user_name]);
    if(!array_intersect($userinfo_rs['role'], ['admin','super_admin'])){
        $current_purpose = 2;
    }

    $update = array();
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
    $update['audit_state'] = 1;//审核状态 待审核  0--待提交,1 待审核,--2审核通过,3--审核驳回
    $update['audit_time'] = date("Y-m-d H:i:s");
    $update['keyword'] = "{".implode(",",$current['keyword'])."}";

    $update['upload_source'] = $current['upload_source'];
    $update['is_signature'] = $current['is_signature'];
    $update['original_author'] = $current['original_author'];
    $update['purchase_source'] = $current['purchase_source'];

    DB::TransBegin();
    QImgKeyword::push($current['keyword']);
    $rs = DB::Update("public.img",$img_info['id'],$update,"id");
    if(!$rs){
        DB::TransRollback();
        $json['status'] = 107;
        $json['message'] = "保存数据失败";
        $json['message_ext'] = "img";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }

    $extend = array();
    $extend["sight_info"] = $sight_info;
    $extend_json = json_encode($extend,JSON_UNESCAPED_UNICODE);

    $sql = "update public.img_ext set extend=extend||'{$extend_json}' where img_id='{$img_info['id']}'";

    if(!DB::Query($sql)){
        DB::TransRollback();
        $json['status'] = 107;
        $json['message'] = "保存数据失败";
        $json['message_ext'] = "img_ext";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }
    /*
     * 记录操作流水：首次提交submit
     * 跟柱哥确认过，只有首次提交会走这--是提交审核，不是单纯的保存
     */
    
    //计算积分--只有第一次提交计算任务分
    
    $params_record = [
        'img_id' => $img_info['id'],
        'old_data' => $img_info,
        'new_data' => array_merge($update,['keyword_arr'=>$current['keyword']]),
        'operate_type' => 'submit',
        'username' => $login_user_name,
    ];
    
    $record_rs = QImgOperateTrace::recordOperationTracePoint($params_record);
    if( !$record_rs || !$record_rs['ret'] ){
        DB::TransRollback();
        $json['status'] = 107;
        $json['message'] = "保存数据失败";
        $json['message_ext'] = "audit_records";
        $json['message_eid'] = $current['eid'];
        display_json_str_common($json,$callback);
    }
    
    DB::TransCommit();
    $json['status'] = 0;
    $json['message'] = "保存成功";
    display_json_str_common($json,$callback);
}