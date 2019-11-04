<?php

/**
 * 素材审核
 */

class QImgAudit {
    public static $field_value = [
        'title' => '标题',//标题
        'place' => '拍摄地点',//拍摄地点
        'small_type' => '分类',//分类
        'keyword' => '关键词',//关键词
        'purpose' => '用途',//用途
        'purchase_source' => '采购来源',//采购来源
        'upload_source' => '上传作者',//上传作者
        'original_author' => '原始作者',//原始作者
        'is_signature' => '需署名',//需署名
    ];
    
    /*
     * 获取图片修改diff字段key
     */
    public static function getUpdateKey($params=[]){
        $old_data = $params['old_data'] ? $params['old_data'] : [];//old_data原始数据
        $new_data = $params['new_data'] ? $params['new_data'] : [];//new_data新修改数据
        $diff_data = [];
        
        $diff_origin = [
            'title',//标题
            'place',//拍摄地点
            'small_type',//小分类
            'keyword',//关键词
            'purpose',//用途
            'purchase_source',//采购来源
            'upload_source',//上传作者
            'original_author',//原始作者
            'is_signature',//需署名
        ];
        $old_data["small_type"] = json_encode(json_decode($old_data["small_type"], true));

        foreach( $diff_origin as $k => $v ){
            if( is_array($old_data[$v]) ){
                if( array_diff($old_data[$v], $new_data[$v]) ){
                    $diff_data[] = $v;
                }
            }elseif( $old_data[$v] != $new_data[$v] ){
                $diff_data[] = $v;
            }
        }
        return $diff_data;
    }
    
    /*
     * 审核状态数量
     */
    public static function getAuditCounts(){
        //获取不同审核状态的图片数量
        $select_sql = " SELECT audit_state, COUNT(1) AS count FROM "
                .QImgSearch::$imgTableName
                ." WHERE audit_state > 0 AND ( is_del = 'f' OR audit_state = 4 ) GROUP BY audit_state ";
        
        $db_rs = DBSLAVE::GetQueryResult($select_sql, false);
        if( $db_rs ){
            foreach( $db_rs as $k => $v ){
                if( $v['audit_state'] == 1 ){//待审核
                    $pending_count = $v['count'];
                }elseif( $v['audit_state'] == 2 ){//通过
                    $passed_count = $v['count'];
                }elseif( $v['audit_state'] == 3 ){//被驳回
                    $reject_count = $v['count'];
                }elseif( $v['audit_state'] == 4 ){//被审核过，删除
                    $remove_count = $v['count'];
                }
            }
        }
        
        return [
            'status' => 0,
            'message' => '查询成功',
            'data' => [
                'pending_count' => $pending_count > 0 ? $pending_count : '0',
                'passed_count' => $passed_count > 0 ? $passed_count : '0',
                'reject_count' => $reject_count > 0 ? $reject_count : '0',
                'remove_count' => $remove_count > 0 ? $remove_count : '0',
            ],
        ];
    }
    /*
     * 审核状态数量--系统驳回
     */
    public static function getSystemRejectCounts(){
        //获取不同审核状态的图片数量
        $select_sql = " SELECT COUNT(1) AS count FROM "
                .QImgSearch::$imgTableName
                ." WHERE audit_state > 0 AND ( is_del = 'f' OR audit_state = 4 ) ";
        
        $select_sql.= " AND audit_user = '系统审核' AND ( system_check->'img' = '2' OR system_check->'word' = '2' )  ";
        $db_rs = DB::GetQueryResult($select_sql);
        
        return $db_rs['count'] ? $db_rs['count'] : '0';
    }
    
    /*
     * 查询审核素材列表：待审核、已通过、未通过、已下架
     */
    public static function getAuditList($params=[]){
        if( !$params['audit_state'] || !in_array($params['audit_state'], [1,2,3,4]) ){
            return [
                'ret' => false,
                'data' => [],
                'msg' => '审核状态参数有误',
            ];
        }
        // 待审核（提交审核时间由近及远）、已通过/未通过（审核时间由近及远）、已下架（图片被删除时间由近及远）、系统驳回（驳回时间由近及远）
        // 待审核1  已通过2  未通过3 已下架4
        if (in_array($params['audit_state'], [1, 2, 3])) {
            $params['sort_by'] = 1;
            $params['sort_type'] = 2;
        }elseif ($params['audit_state'] == 4) {
            $params['sort_by'] = 8;
            $params['sort_type'] = 2;
        }

        $img_list = self::getAuditImgs($params);
        return [
            'ret' => true,
            'msg' => '查询成功',
            'data' => $img_list ? $img_list : [],
        ];
    }
    /*
     * 查询审核素材列表：待审核、已通过、未通过、已下架
     * 系统审核列表查询--驳回
     */
    public static function getSystemRejectList($params=[]){
        $params['audit_state'] = 3;
        
        $params['sort_by'] = 6;
        if( $params['audit_state'] == 1 ){//作者提交的时间由远及近
            $params['sort_type'] = 1;
        }
        
        if( in_array($params['audit_state'], ['2', '3']) ){//按审核时间
            $params['sort_by'] = 1;
        }
        
        $img_list = self::getAuditImgs($params);
        return [
            'ret' => true,
            'msg' => '查询成功',
            'data' => $img_list ? $img_list : [],
        ];
    }
    
    /*
     * 查询每一条图片的审核记录--只有待审核列表需要查询
     */
    public static function getAuditArrDetails($params=[]){
        global $system_domain;
        if( !$params || !is_array($params) ){
            return false;
        }
        
        $rs = [];
        $eidList = array_unique(array_column((array) $params, "eid"));
        $eidAndUsername = array_replace_key(QAuth::getImgUsername($eidList), 'eid');
        $usernameList = array_filter(array_unique(array_merge(array_column($params, "username"), array_column($params, "audit_user"))), function($item){
            return ! empty($item);
        });
        $usernameAndAvatar = empty($usernameList) ? [] : array_replace_key(QAuth::userAvatar($usernameList), "username");
        foreach( $params as $k => $v ){
            $keyword_add = [];
            $new_update_key = [];
            $keyword = json_decode($v['keyword_arr'], true);
            
            if( $v['is_del'] == 't' && $v['audit_state'] == 4 ){
                $show_state = '已下架';
                $show_operate = '删除图片';
            }elseif( $v['audit_state'] == 2 ){
                $show_state = '已通过';
                $show_operate = '审核通过';
            }elseif( $v['audit_state'] == 3 ){
                $show_state = '未通过';
                $show_operate = '审核驳回';
            }elseif( !$v['audit_user'] ){//如果是初次提交，不需要查询
                $show_state = '未审核';
                $show_operate = '上传';
            }else{//第二次以后的修改，需要diff处理
                $show_state = '新修改';
                $show_operate = '修改';
                
                //查询图片最近一次被审核记录
                $params_audit_trace = [
                    'img_id' => $v['id'],
                ];
                $img_audit_trace = QImgOperateTrace::getOneAuditTrace($params_audit_trace);
                $data_old = json_decode($img_audit_trace['old_data'], true);
                
                //获取新修改的字段
                $params_new_update = [
                    'old_data' => $data_old,
                    'new_data' => $v,
                ];
                $new_update_key = self::getUpdateKey($params_new_update);
                
                //判断如果keyword修改后，需要diff出原有和新增加
                if(in_array('keyword', $new_update_key)){
                    $params_keyword = [
                        'keyword_old' => json_decode($data_old['keyword_arr'],true),
                        'keyword_new' => json_decode($v['keyword_arr'],true),
                    ];
                    $keyword_diff = self::getKeywordDiff($params_keyword);
                    $keyword = $params_keyword['keyword_new'];//当前关键词
                    $keyword_origin = $keyword_diff['keyword_origin'];//没有就是全部是新增
                    $keyword_add = $keyword_diff['keyword_add'];//新增
                }
            }
            //拍摄地点

            $location_json = json_decode($v['location'],true);
            $location = array_values(array_filter(array_unique(json_decode($v['location'],true))));


            //拍摄地点
            $location = array_values(array_filter([
                $location_json['country'],
                $location_json['province'],
                $location_json['city'],
                $location_json['county'],
            ]));

            //2019-01-25新增 拍摄地点-new-详情页点击跳转用
            $location_detail__key = [
                'country',
                'province',
                'city',
                'other',
            ];

            $location_detail_arr = [
                'country' => '',
                'province' => '',
                'city' => '',
                'other' => [],
            ];

            foreach( $location_json as $kk => $vv ){
                if( $vv ){
                    if( current($location_detail__key) == 'other' ){
                        $location_detail_arr['other'][] = $vv;
                    }else{
                        $location_detail_arr[array_shift($location_detail__key)] = $vv;
                    }
                }
            }

            if( $v['place'] ){
                $location[] = $v['place'];
                $location_detail_arr['other'][] = $v['place'];
            }

            if( $v['place'] ){
                $location[] = $v['place'];
            }

            $small_img = QImg::getImgUrlResize(array(
                "img"=>$v['url'],
                "width"=>$v['width'],
                "height"=>$v['height'],
                "r_width"=>280,
                "r_height"=>280,
                'system_domain'=>$system_domain,
                "in"=>"inner_domain")
            );//图片上传的缩略图地址或者是缩略图
            $big_img = QImg::getImgUrlResize(array(
                "img"=>$v['url'],
                "width"=>$v['width'],
                "height"=>$v['height'],
                "r_width"=> 1000,
                "r_height"=> 1000,
                'system_domain'=>$system_domain,
                "in"=>"inner_domain")
                );//图片上传的缩略图地址或者是缩略图

            //图片元素被驳回
            $system_check = json_decode($v['system_check'], true);
            if( $v['audit_state'] == 3 && $system_check['img'] == 2 ){
                $system_check_img = 2;
            }else{
                $system_check_img = 0;
            }
            
            //2019-03-06 暂时修改系统驳回的图片可以操作审核通过
            $system_check_img = 0;

            $rs[] = [
                'eid' => $v['eid'],
                'system_check_img' => $system_check_img,
                'domain_id' => $v['domain_id'],
                'width' => $v['width'],
                'height' => $v['height'],
                'title' => $v['title'],
                'small_img' => $small_img,//图片上传的缩略图地址或者是缩略图
                'big_img' => $big_img,//图片上传的缩略图地址或者是缩略图
                'ext' => $v['ext'],
                'download' => $v['download'] ? $v['download'] : '0',
                'audit_state' => $v['audit_state'],
                'audit_user' => $v['audit_user'],
                'audit_username' => $usernameAndAvatar[$v['audit_user']]["name"],
                'reject_reason' => array_filter(pgarray2array($v['reject_reason']), function($item){
                    return ! empty($item) && $item != '""' && $item != '\'\'';
                }),
                'place' => $v['place'] ? $v['place'] : "",
                'city_id' => $v['city_id'] ? $v['city_id'] : "",
                'purpose' => $v['purpose'],
                'small_type' => json_decode($v['small_type'], true),
                'extend' => QImgInfo::exifInfo(['extend'=>$v['extend']]),
                'location' => array_values(array_unique($location)),
                'filesize' => byte_convert($v['filesize']),
                'new_update_key' => $new_update_key ? $new_update_key : [],
                'keyword_add' => $keyword_add ? $keyword_add : [],
                'keyword_origin' => $keyword_origin ? $keyword_origin : [],
                'keyword' => $keyword,
                'operate_info' => [
                    'operate_time' => date("Y-m-d H:i:s", strtotime($v['update_time'])),
                    'realname' => $v['name'],
                    'state' => $show_state,
                    'operate' => $show_operate,
                ],
                'deletable' => strpos($v["url"], "delete") === false,
                'upload_source' => $v['upload_source'] ?: "",
                'purchase_source' => $v['purchase_source'] ?: "",
                'original_author' => $v['original_author'] ?: "",
                'is_signature' => $v['is_signature'] == 't',
                'star' => $v['star'] == 't',
                'uploader_username' => $eidAndUsername[$v['eid']]['uploader_username'],
                'uploader_realname' => $eidAndUsername[$v['eid']]['uploader_realname'],
                'del_time' => $v['del_time'] ? date("Y-m-d H:i:s", strtotime($v['del_time'])) : '',
                'upload_time' => $v['create_time'] ? date("Y-m-d H:i:s", strtotime($v['create_time'])) : '',
            ];
            
        }
        
        return $rs;
    }
    
    /*
     * diff-keyword
     * keyword_origin  以前就已经存在的
     * keyword_add  新增加的
     */
    public static function getKeywordDiff($params=[]){
        $keyword_old = $params['keyword_old'] ? $params['keyword_old'] : [];
        $keyword_new = $params['keyword_new'] ? $params['keyword_new'] : [];

        $keyword_origin = [];
        $keyword_add = [];
        foreach( $keyword_new as $k => $v ){
            if(in_array($v, $keyword_old)){
                $keyword_origin[] = $v;
            }else{
                $keyword_add[] = $v;
            }
        }
        return [
            'keyword_origin' => $keyword_origin,
            'keyword_add' => $keyword_add,
        ];
    }
    
    /*
     * 查询获取图片列表
     * 入参说明：
     * offset和limit
     * 首页\list页\我的个人中心
     * 关键字 keyword array
     * 图片格式 ext array
     * 图片大小  size_type array
     * 大标题  big_type string
     * 小标题 small_type array
     * 拍摄地点：array，key分别为country--province--city--address
     * 排序方式：0-默认；1-最新上传；2-下载最多;3-收藏最多;4-点赞最多
     * 
     */
    public static function getAuditImgs($params=[]){
        $offset = $params['offset'] ? $params['offset'] : 0;//偏移量-分页用
        $limit = $params['limit'] ? $params['limit'] : '';//数量限制-分页用
        $sort_by = $params['sort_by'] ? $params['sort_by'] : '';//排序方式
        unset($params["offset"]);
        unset($params["limit"]);
        unset($params["sort_by"]);
        
        $sql_where = QImgSearch::getWhereSql($params);

        //用户名
        if( $params['audit_state'] == 1 ){
            $operate_user = 'username';
        }elseif( $params['audit_state'] == 4 ){
            $operate_user = 'del_user';
        }elseif( in_array($params['audit_state'],[2,3]) ){
            $operate_user = 'audit_user';
        }
        
        $sql = "SELECT i.id AS id, i.eid AS eid, i.update_time AS update_time, 
                 i.audit_state AS audit_state, i.is_del AS is_del, i.location AS location, 
                 i.audit_desc AS audit_desc, i.reject_reason AS reject_reason, 
                 i.place AS place, i.url AS url, i.width AS width, i.logo_url AS logo_url, 
                 i.download AS download, i.system_check AS system_check , 
                 i.height AS height, i.title AS title, i.ext AS ext, 
                 e.extend AS extend,
                 i.small_type AS small_type, i.purpose AS purpose, i.city_id AS city_id, 
                 i.purpose AS purpose, i.filesize AS filesize, 
                 i.audit_user AS audit_user, array_to_json(i.keyword) as keyword_arr,
                 CASE WHEN su.name IS NOT NULL THEN su.name ELSE i.audit_user END AS name,
                 i.upload_source, i.purchase_source, i.original_author, i.is_signature, i.star, i.del_time, i.create_time FROM "
                . QImgSearch::$imgTableName." i LEFT JOIN ".QImgSearch::$imgExtTableName." e ON i.id = e.img_id LEFT JOIN "
                . QImgSearch::$userTableName." su ON i.{$operate_user} = su.username "
                . $sql_where;
        
        if($params['audit_state']=3&&array_key_exists('is_system_check', $params)&&$params['is_system_check']==1){
            $sql.= " AND i.audit_user = '系统审核' AND ( i.system_check->'img' = '2' OR i.system_check->'word' = '2' )  ";
        }

        //排序
        $sql.= QImgSearch::getOrderSql(['sort_by'=>$sort_by,'sort_type'=>$params['sort_type'],'table_code'=> 'i']);
        
        //分页用
        if( $offset >= 0 ){
            $sql.= " OFFSET {$offset}";
        }
        if( $limit > 0 ){
            $sql.= " limit {$limit}";
        }

        //获取图片列表
        $db_rs = DBSLAVE::GetQueryResult($sql, false);
        return $db_rs ? $db_rs : [];
    }
    /*
     * 验证imgid提交记录
     */
    public static function getImgDistinct($params=[]){
        $id = $params['id'] ? $params['id'] : [];
        if( !$id ){
            return [];
        }
        
        $id_str = implode("','", $id);
        $sql = " SELECT DISTINCT(img_id) FROM ".QImgSearch::$auditRecordsTableName." WHERE img_id in ('{$id_str}') ";
        $db_rs = DB::GetQueryResult($sql, false);
        return array_column($db_rs, 'img_id');
    }
}
