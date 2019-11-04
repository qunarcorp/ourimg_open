<?php

/**
 * 图片查询搜索、定义表名
 */

class QImgSearch {
    
    //表
    public static $imgTableName = 'public.img';//图片表
    public static $imgExtTableName = 'public.img_ext';//图片扩展信息表
    public static $praiseTableName = 'public.praise';//点赞表
    public static $favoriteTableName = 'public.favorite';//收藏表
    public static $browseTableName = 'public.browse_trace';//浏览表
    public static $downloadTableName = 'public.download_history';//下载表
    public static $userTableName = 'public.system_user';//普通用户表
    public static $searchTableName = 'public.search';//用户关键字搜索记录表
    public static $albumTableName = 'public.img_album';//专辑表
    public static $imgDelRecordTableName = 'public.img_del_record';//图片删除表
    public static $auditRecordsTableName = 'public.audit_records';//图片操作流水表
    public static $pointsTraceTableName = 'public.points_trace';//用户积分流水列表
    public static $taskCityTableName = 'public.task_city_list';//城市任务列表
    public static $dailyTaskTableName = 'public.daily_task_list';//用户每日任务完成情况记录
    public static $productTableName = 'public.product_info';//商品表
    public static $orderTableName = 'public.exchange_order';//商品兑换订单表
    public static $searchRecordTableName = 'public.search_record';//商品兑换订单搜索记录表
    public static $pointRulesTableName = 'public.point_rules';//积分细则
    public static $activityTasksTableName = 'public.activity_tasks';//活动任务表
    public static $activityImgRelationTableName = 'public.activity_img_relation';//活动任务和图片对应表
    
    
    //日志
    public static $log_prefer = 'img';
    public static $log_action = 'search';

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
     */
    public static function getImgs($params=[]){
        $offset = $params['offset'] ? $params['offset'] : 0;//偏移量-分页用
        $limit = $params['limit'] ? $params['limit'] : '';//数量限制-分页用
        $sort_by = $params['sort_by'] ? $params['sort_by'] : '';//排序方式
        unset($params["offset"]);
        unset($params["limit"]);
        unset($params["sort_by"]);

        $params["keyword"] = array_map(function($item){
            return str_replace(['(', ')'], ['\(', '\)'], $item);
        }, $params["keyword"]);

        $sql_where = self::getWhereSql($params);
        
        $sql = "SELECT i.id AS id, i.eid AS eid, i.update_time AS update_time, 
                 i.user_ip AS user_ip, i.create_time AS create_time, i.del_time AS del_time, 
                 i.audit_desc AS audit_desc, i.audit_user AS audit_user, i.audit_time AS audit_time, 
                 i.praise AS praise, i.browse AS browse, i.size_type AS size_type, i.logo_url AS logo_url, 
                 i.domain_id AS domain_id, i.username AS username, i.file_name AS file_name, 
                 i.big_type AS big_type, i.download AS download, i.favorite AS favorite, 
                 i.audit_state AS audit_state, i.is_del AS is_del, i.location AS location, 
                 i.location->>'country' AS country, 
                 i.location->>'province' AS province, 
                 i.location->>'city' AS city, 
                 i.location->>'county' AS county, 
                 e.extend AS extend, 
                 i.place AS place, i.url AS url, i.width AS width, i.reject_reason AS reject_reason, 
                 i.height AS height, i.title AS title, i.ext AS ext, i.system_check AS system_check, 
                 i.small_type AS small_type, i.purpose AS purpose, i.city_id AS city_id, 
                 i.purpose AS purpose, i.filesize AS filesize, array_to_json(i.keyword) as keyword_arr,
                 i.upload_source, i.purchase_source, i.original_author, i.is_signature, i.star, i.del_time, i.create_time FROM "
                .self::$imgTableName." i JOIN ".self::$imgExtTableName." e ON i.id = e.img_id "
                ." LEFT JOIN ".self::$userTableName." su ON i.username = su.username ".$sql_where;
        
        //排序
        $sql.= self::getOrderSql(['sort_by'=>$sort_by, 'table_code' => 'i']);
        
        //分页用
        if( $offset >= 0 ){
            $sql.= " OFFSET {$offset}";
        }
        if( $limit > 0 ){
            $sql.= " limit {$limit}";
        }
        
        //开始时间
        $sql_begin_time = microtime(true);

        //获取图片列表
        $db_rs = DBSLAVE::GetQueryResult($sql, false);
        
        
        //sql执行时间
        $sql_exec_time = sprintf("%.2f",microtime(true)-$sql_begin_time);
        $message = "图片搜索，sql如下：". $sql."；开始时间：".$sql_begin_time."；sql执行时间：".$sql_exec_time;
        QLog::info(self::$log_prefer,self::$log_action,$message);
        
        
        return $db_rs ? $db_rs : [];
    }
    /*
     * 处理查询出来的图片数组
     * 传入查询出来的img图片数组--$params
     */
    public static function dealImgInfos($params=[],$params2=[]){
        global $system_domain;
        global $login_user_name;
        if( !$params || !is_array($params) ){
            return false;
        }
        
        //获取用户信息--判断是否有下载权限
        $userinfo_rs = QImgPersonal::getUserInfo(['username'=> $login_user_name]);
        if(array_intersect($userinfo_rs['role'], ['design','admin','super_admin'])){
            $download_permission = true;
        }

        if (!array_key_exists('img_type',$params2) ||   !in_array($params2['img_type'],["inner_domain","out_domain"]) ){
            $params2['img_type'] = 'inner_domain';
        }

        //获取图片id
        $params_imgid = array_column($params, 'id');
        
        if(array_key_exists('username', $params2)&&$params2['username']){
            //获取用户收藏图片
            $ids_rs = QImgInfo::getFavoriteInfo($params_imgid, $params2);
            $ids_rs = array_column($ids_rs, 'img_id');
            //获取用户点赞，喜欢图片
            $ids_rs2 = QImgInfo::getPraiseInfo($params_imgid, $params2);
            $ids_rs2 = array_column($ids_rs2, 'img_id');
            //获取图片是否在购物车中--当前登录用户
            $ids_rs3 = QImgShopCart::getIsAddShop($params2['username'], $params_imgid);
        }else{
            $ids_rs = [];
            $ids_rs2 = [];
            $ids_rs3 = [];
        }

        $rs = [];
        $eidList = array_unique(array_column((array) $params, "eid"));
        $eidAndUsername = array_replace_key(QAuth::getImgUsername($eidList), 'eid');
        $usernameList = array_filter(array_unique(array_merge(array_column($params, "username"), array_column($params, "audit_user"))), function($item){
            return ! empty($item);
        });
        $usernameAndAvatar = empty($usernameList) ? [] : array_replace_key(QAuth::userAvatar($usernameList), "username");

        $usernameAndAvatar['系统审核']['name'] = "系统驳回";
        foreach( $params as $k => $v ){
            //收藏
            if( $ids_rs && is_array($ids_rs) ){
                if(in_array($v['id'], $ids_rs)){
                    $user_favorited = true;
                }else{
                    $user_favorited = false;
                }
            }
            //点赞，喜欢
            if( $ids_rs2 && is_array($ids_rs2) ){
                if(in_array($v['id'], $ids_rs2)){
                    $user_praised = true;
                }else{
                    $user_praised = false;
                }
            }
            
            //拍摄地点
            $location = array_values(array_filter([
                $v['country'],
                $v['province'],
                $v['city'],
                $v['county'],
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
            
            foreach( $location as $kk => $vv ){
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
            $location = array_values($location);
            
            //关键词
            $keyword_arr = json_decode($v['keyword_arr'],true);
            
            //大图来源：是否需要添加logo
            $big_url_type = $params2['big_url_type'] ? $params2['big_url_type'] : '';
            
            //判断删除状态，如果已删除，不展示原有图片
            if( $v['is_del'] == 't' ){
                $small_img500 = $big_img = $small_img = $v['url'] = "https://imgs.qunarzz.com/vc/5e/16/08/837ac97fe8085ae131e0932216.png_92.png";
            }else{
                $small_img500 = QImg::getImgUrlResize(array(
                    "img"=>$v['url'],
                    "width"=>$v['width'],
                    "height"=>$v['height'],
                    "r_width"=>500,
                    "r_height"=>500,
                    'system_domain'=>$system_domain,
                    "in"=>$params2['img_type'])
                    );//图片原图
                $small_img500heihgt = QImg::getImgUrlResizeHeight(array(
                    "img"=>$v['url'],
                    "width"=>$v['width'],
                    "height"=>$v['height'],
                    "r_height"=>500,
                    'system_domain'=>$system_domain,
                    "in"=>$params2['img_type'])
                    );//图片瀑布流图-限制500高度
                $big_img = QImg::getImgUrlResize(array(
//                    "img"=>$v['url'],
                    "img"=> $big_url_type == 'my_upload' ? $v['url'] : ($v['logo_url']?$v['logo_url']:$v['url']),
                    "width"=>$v['width'],
                    "height"=>$v['height'],
                    "r_width"=> 1000,
                    "r_height"=> 1000,
                    'system_domain'=>$system_domain,
                    "in"=>$params2['img_type'])
                    );//图片上传的缩略图地址或者是缩略图
                $small_img = QImg::getImgUrlResize(array(
                    "img"=>$v['url'],
                    "width"=>$v['width'],
                    "height"=>$v['height'],
                    "r_width"=>280,
                    "r_height"=>280,
                    'system_domain'=>$system_domain,
                    "in"=>$params2['img_type'])
                    );//图片上传的缩略图地址或者是缩略图
                $small_img_touch = QImg::getImgUrlResize(array(
                    "img"=>$v['url'],
                    "width"=>$v['width'],
                    "height"=>$v['height'],
                    "r_width"=>280,
                    "r_height"=>280,
                    'system_domain'=>$system_domain,
                    "in"=>"out_domain")
                    );//图片上传的缩略图地址或者是缩略图
            }
            
            //图片元素被驳回
            $system_check = json_decode($v['system_check'], true);
            if( $v['audit_state'] == 3 && $system_check['img'] == 2 ){
                $system_check_img = 2;
            }else{
                $system_check_img = 0;
            }
            
            $rs[] = [
                'id' => $v['id'],
                'eid' => $v['eid'],
                'system_check_img' => $system_check_img,
                'location_detail_arr' => $location_detail_arr,
                'domain_id' => $v['domain_id'],
                'width' => $v['width'],
                'height' => $v['height'],
                'show_edit_label' => $v['create_time'] == $v['update_time'] ? 1 : 0,
                'title' => $v['title'] ? $v['title'] : basename ($v['file_name'],strrchr($v['file_name'],".")),//原始的图片名称,
                'is_del' => $v['is_del'] == 'f' ? false : true,
                'url' => $v['url'],
                'small_img500' => $small_img500,//图片原图
                'small_img500heihgt' => $small_img500heihgt,//图片原图
                'big_img' => $big_img,//图片上传的缩略图地址或者是缩略图
                'small_img' => $small_img,//图片上传的缩略图地址或者是缩略图
                'small_img_touch' => $small_img_touch,//图片上传的缩略图地址或者是缩略图
                'download' => $v['download'] ? $v['download'] : 0,
                'favorite' => $v['favorite'] ? $v['favorite'] : 0,
                'browse' => $v['browse'] ? $v['browse'] : 0,
                'praise' => $v['praise'] ? $v['praise'] : 0,
                'user_favorited' => $user_favorited ? true : false,
                'user_praised' => $user_praised ? true : false,
                'username' => $v['username'],
                'ext' => $v['ext'],
                'audit_state' => $v['audit_state'],
                'place' => $v['place'] ? $v['place'] : "",
                'city_id' => $v['city_id'] ? $v['city_id'] : "",
                'purpose' => $v['purpose'],
                'size_type' => $v['size_type'],
                'big_type' => $v['big_type'],
                'small_type' => json_decode($v['small_type'], true) ?: [],
                'extend' => QImgInfo::exifInfo(['extend'=>$v['extend']]),
                'audit_user' => $v['audit_user'],
                'audit_username' => $usernameAndAvatar[$v['audit_user']]["name"],
                'audit_desc' => $v['audit_desc'] ? $v['audit_desc'] : "",
//                'reject_reason' => $v['reject_reason'] ? $v['reject_reason'] : "",
                'reject_reason' => pgarray2array($v['reject_reason']),
                'location' => array_values(array_unique($location)),
                'filesize' => byte_convert($v['filesize']),
                'current_time' => date_diff_str($v['create_time']),
                'audit_time' => date("Y-m-d H:i:s", strtotime($v['audit_time'])),
                'operate_time' => $v['operate_time'] ? date("Y-m-d H:i:s", strtotime($v['operate_time'])) : "",//下载、收藏、足迹用
                'operate_date' => $v['operate_time'] ? date("Y-m-d", strtotime($v['operate_time'])) : "",//下载、收藏、足迹用
                'keyword' => $keyword_arr ? $keyword_arr : [],
                'user_shopcart' => $ids_rs3[$v['id']] ? true : false,
                'download_permission' => $download_permission || $v['username'] == $login_user_name ? true : false,
                'realname' => $usernameAndAvatar[$v['username']]["name"],
                'avatar' => $usernameAndAvatar[$v['username']]["img"],
                'upload_source' => $v['upload_source'] ?: '',
                'purchase_source' => $v['purchase_source'] ?: '',
                'original_author' => $v['original_author'] ?: '',
                'is_signature' => $v['is_signature'] == 't',
                'star' => $v['star'] == 't',
                'uploader_username' => $eidAndUsername[$v['eid']]['uploader_username'],
                'uploader_realname' => $eidAndUsername[$v['eid']]['uploader_realname'],
                'del_time' => $v['del_time'] ? date("Y-m-d H:i:s", strtotime($v['del_time'])) : '',
                'upload_time' => date("Y-m-d H:i:s", strtotime($v['create_time'])),
                'simple_upload_time' => date("Y-m-d H:i", strtotime($v['create_time'])),
            ];
        }
        
        return $rs ? $rs : [];
    }

    /*
     * 获取图片数量
     */
    public static function getImgCount($params=[]){
        $sql_where = self::getWhereSql($params);
        $count_sql = "SELECT COUNT(1) FROM ".self::$imgTableName." i LEFT JOIN ".self::$userTableName." su ON i.username = su.username ".$sql_where;
        //获取图片数量
        $db_count_rs = DB::GetQueryResult($count_sql);
        
        return $db_count_rs['count'] ? $db_count_rs['count'] : 0;
    }
    
    /*
     * 整理sql的where条件
     */
    public static function getWhereSql($params=[]){
        $eid = $params['eid'] ? $params['eid'] : '';//eid-详情页用
        $keyword = $params['keyword'] ? $params['keyword'] : '';//关键字
        $big_type = $params['big_type'] ? $params['big_type'] : '';//大标题、分类
        $small_type = $params['small_type'] ? $params['small_type'] : '';//小标题、分类
        $username = $params['username'] ? $params['username'] : '';//用户名
        $uploadSource = $params['upload_source'] ? $params['upload_source'] : '';//上传来源
        $location = $params['location'] ? $params['location'] : '';//拍摄地址
        $ext = $params['ext'] ? $params['ext'] : '';//格式
        $size_type = $params['size_type'] ? $params['size_type'] : '';//图片大小
        $audit_state = strlen($params['audit_state']) > 0 ? $params['audit_state'] : 2;//图片状态
        $is_del = in_array($params['is_del'], ['f','t','all']) ? $params['is_del'] : 'f';//图片删除状态
        $showEditLabel = is_null($params['show_edit_label']) ? null : intval($params['show_edit_label']);//编辑、未编辑

        if( !$is_del ){
            $sql_where = " WHERE i.is_del = 'f' ";
        }elseif( $is_del != 'all' ){
            $sql_where = " WHERE i.is_del = '{$is_del}' ";
        }else{
            $sql_where = " WHERE 1 = 1 ";
        }
        
        if( $audit_state!='all' ){
            $sql_where.= " AND i.audit_state = '{$audit_state}' ";
        }
        
        if( $username ){
            $sql_where.= " AND i.username = '{$username}' ";
        }

        if( $uploadSource ){
            $sql_where.= " AND i.upload_source = '{$uploadSource}' ";
        }
        
        //$big_type 走配置,给前端，写到接口中
        if( $big_type && is_numeric($big_type) ){
            $sql_where.= " AND i.big_type = '{$big_type}' ";
        }
        
        //eid--详情页查询
        if( $eid && is_numeric($eid) ){
            $sql_where.= " AND i.eid = '{$eid}' ";
        }
        
        //关键字--搜索图片标题【模糊】和关键词字段
        if( $keyword && is_array($keyword) ){
            $keyword_str = implode("','", $keyword);
            $keyword_str2 = implode("|", $keyword);

            //2019-01-16关键字搜索增加拍摄地点字段
            $keywordCondition = [
                " i.keyword::text[] && ARRAY['".$keyword_str."'] ",
                " i.title ~ '{$keyword_str2}' ",
                " i.place ~ '{$keyword_str2}' ",
                " i.location->>'country' ~ '{$keyword_str2}' ",
                " i.location->>'province' ~ '{$keyword_str2}' ",
                " i.location->>'city' ~ '{$keyword_str2}' ",
                " i.location->>'county' ~ '{$keyword_str2}' ",
                " i.eid::text ~ '{$keyword_str2}' ",
                " i.purchase_source::text ~ '{$keyword_str2}' ",
                " i.original_author::text ~ '{$keyword_str2}' ",
                " i.username::text ~ '{$keyword_str2}' ",
                " su.name::text ~ '{$keyword_str2}' ",
            ];

            $sql_where.= " AND ( " . implode(" OR ", $keywordCondition) . " ) ";
        }
        
        //$ext-图片格式
        if( $ext && is_array($ext) ){
            $ext_str = implode("','", $ext);
            $sql_where.= " AND i.ext IN ('{$ext_str}') ";
        }
        
        //$size_type-图片大小
        if( $size_type && is_array($size_type) ){
            $size_type_str = implode("','", $size_type);
            $sql_where.= " AND i.size_type IN ('{$size_type_str}') ";
        }
        
        //small_type-小标题
        if( $small_type && is_array($small_type) ){

            $small_type_str = implode("','", $small_type);
            $sql_where.= " AND i.small_type::jsonb ?| array['{$small_type_str}'] ";
        }
        
        //拍摄地点查询--country--province--city--address
        if( $location && is_array($location) ){
            if(array_key_exists('country', $location)&&$location['country']){
                $sql_where.= " AND i.location->>'country' = '{$location['country']}' ";
            }
            if(array_key_exists('province', $location)&&$location['province']){
                $sql_where.= " AND i.location->>'province' = '{$location['province']}' ";
            }
            if(array_key_exists('city', $location)&&$location['city']){
                $sql_where.= " AND i.location->>'city' = '{$location['city']}' ";
            }
            if(array_key_exists('address', $location)&&$location['address']){
                $sql_where.= " AND i.location->>'county' = '{$location['address']}' ";
            }
        }

        if ($showEditLabel === 1) {
            $sql_where .= " AND i.create_time = i.update_time ";
        }elseif ($showEditLabel === 0) {
            $sql_where .= " AND i.create_time != i.update_time ";
        }
        
        return $sql_where;
    }
    
    /*
     * 获取一条img数据
     */
    public static function getOneImg($params=[]){
        $eid = $params['eid'] ? $params['eid'] : 0;//eid
        $id = $params['id'] ? $params['id'] : 0;//id
        
        if( $eid ){
            $sql_where = " WHERE i.eid = '{$eid}' ";
        }elseif( $id ){
            $sql_where = " WHERE i.id = '{$id}' ";
        }
        
        $sql = "SELECT i.*, array_to_json(i.keyword) as keyword_arr,
                 i.location->>'country' AS country, 
                 i.location->>'province' AS province, 
                 i.location->>'city' AS city, 
                 i.location->>'county' AS county
                 ,e.extend AS extend
                  FROM "
                .self::$imgTableName." i JOIN ".self::$imgExtTableName." e ON i.id = e.img_id ".$sql_where;
        
        //获取图片
        $db_rs = DBSLAVE::GetQueryResult($sql);
        return $db_rs ? $db_rs : [];
    }
    /*
     * 根据eid获取img图片
     */
    public static function getImgsByeid($params=[]){
        $eid = $params['eid'] ? $params['eid'] : [];//eid
        $id = $params['id'] ? $params['id'] : [];//id
        
        if( $eid ){
            $eid_str = implode("','", $eid);
            $sql_where = " WHERE eid IN ('{$eid_str}') ";
        }else{
            $id_str = implode("','", $id);
            $sql_where = " WHERE id IN ('{$id_str}') ";
        }
        
        
        $sql = "SELECT *, array_to_json(keyword) as keyword_arr FROM "
                .self::$imgTableName.$sql_where;
        
        //获取图片
        $db_rs = DB::GetQueryResult($sql, false);
        return $db_rs ? $db_rs : [];
    }

    /*
     * 获取我的上传图片--del
     */
    public static function getMyUploads($params=[]){
        $offset = $params['offset'] ? $params['offset'] : 0;//偏移量-分页用
        $limit = $params['limit'] ? $params['limit'] : '';//数量限制-分页用
        $sort_by = $params['sort_by'] ? $params['sort_by'] : '';//排序方式
        $keyword = $params['keyword'] ? $params['keyword'] : [];

        $username = $params['username'] ? $params['username'] : '';//用户名

        if( !$username ){
            return [
                'ret' => false,
                'msg' => '用户信息不能为空',
            ];
        }else{
            $sql_where = " WHERE is_del = 'f' AND username = '{$username}' ";
        }

        $sql = "SELECT *, array_to_json(keyword) as keyword_arr FROM "
            .self::$imgTableName.$sql_where;
        if( $keyword && is_array($keyword) ){
            $keyword_str = implode("','", $keyword);
            $sql.= " AND ( keyword::text[] && ARRAY['".$keyword_str."'] ";
            foreach( $keyword as $k => $v ){
                $sql.= " OR title LIKE '%{$v}%' ";
            }
            $sql.= " ) ";
        }

        //排序
        $sql.= self::getOrderSql(['sort_by'=>$sort_by]);

        //分页用
        if( $offset >= 0 ){
            $sql.= " OFFSET {$offset}";
        }
        if( $limit > 0 ){
            $sql.= " limit {$limit}";
        }

        //获取图片列表
        $db_rs = DB::GetQueryResult($sql, false);
        return [
            'ret' => true,
            'msg' => '查询成功',
            'data' => $db_rs ? $db_rs : [],
        ];
    }

    /*
     * 获取我的上传数量--del
     */
    public static function getMyUploadsCount($params=[]){
        $username = $params['username'] ? $params['username'] : '';//用户名

        if( !$username ){
            return false;
        }else{
            $sql_where = " WHERE is_del = 'f' AND username = '{$username}' ";
        }

        $count_sql = "SELECT COUNT(1) FROM ".self::$imgTableName.$sql_where;

        //获取图片数量
        $db_count_rs = DB::GetQueryResult($count_sql);
        return $db_count_rs['count'] ? $db_count_rs['count'] : 0;
    }
    /*
     * 获取我的上传数量--区分各个tab标签
     */
    public static function getMyUploadTabsCount($params=[]){
        $username = $params['username'] ? $params['username'] : '';//用户名

        if( !$username ){
            return false;
        }else{
            $sql_where = " WHERE is_del = 'f' AND username = '{$username}' ";
        }

        $count_sql = "SELECT 
                 SUM(CASE WHEN audit_state = 0 THEN 1 ELSE 0 END) AS draft_box,
                 SUM(CASE WHEN audit_state = 0 and create_time = update_time THEN 1 ELSE 0 END) AS draft_box_unedited,
                 SUM(CASE WHEN audit_state = 0 and create_time != update_time THEN 1 ELSE 0 END) AS draft_box_edited,
                 SUM(CASE WHEN audit_state = 1 THEN 1 ELSE 0 END) AS under_review,
                 SUM(CASE WHEN audit_state = 2 THEN 1 ELSE 0 END) AS passed,
                 SUM(CASE WHEN audit_state = 3 THEN 1 ELSE 0 END) AS not_pass 
                 FROM ".self::$imgTableName.$sql_where;
        
        //获取图片数量
        $db_count_rs = DB::GetQueryResult($count_sql);
        
        $count_rs = [
            'draft_box' => $db_count_rs['draft_box'] ? $db_count_rs['draft_box'] : '0',
            'draft_box_unedited' => $db_count_rs['draft_box_unedited'] ? $db_count_rs['draft_box_unedited'] : '0',
            'draft_box_edited' => $db_count_rs['draft_box_edited'] ? $db_count_rs['draft_box_edited'] : '0',
            'under_review' => $db_count_rs['under_review'] ? $db_count_rs['under_review'] : '0',
            'passed' => $db_count_rs['passed'] ? $db_count_rs['passed'] : '0',
            'not_pass' => $db_count_rs['not_pass'] ? $db_count_rs['not_pass'] : '0',
        ];
        return $count_rs;
    }
    /*
     * 获取排序
     */
    public static function getOrderSql($params=[]){
        $sort_by = $params['sort_by'] ? $params['sort_by'] : '';//排序方式
        $sort_type = $params['sort_type'] ? $params['sort_type'] : '';//排序方式:默认降序、1升序
        $deleted_after = $params['deleted_after'] ? $params['deleted_after'] : '';//是否需要把删除的图片放最后
        $table_code = $params['table_code'] ? $params['table_code'] : '';//join-用
        
        if( $sort_type == 1 ){//升序
            $sort_type_str = " ASC ";
        }else{
            $sort_type_str = " DESC ";
        }
        
        //判断是否需要将已删除图片置后
        if( $deleted_after ){
            if( $table_code ){
                $sql = " CASE WHEN {$table_code}.is_del = 'f' THEN 1 ELSE 0 END DESC, ";
            }else{
                $sql = " CASE WHEN is_del = 'f' THEN 1 ELSE 0 END DESC, ";
            }
        }
        
        //排序
        if( $sort_by == 1 ){//审核时间
            if( $table_code ){
                $sql = " {$table_code}.audit_time {$sort_type_str} ";
            }else{
                $sql = " audit_time {$sort_type_str} ";
            }
        }elseif( $sort_by == 2 ){//下载
            if( $table_code ){
                $sql = " {$table_code}.download DESC ";
            }else{
                $sql = " download DESC ";
            }
        }elseif( $sort_by == 3 ){//收藏
            if( $table_code ){
                $sql = " {$table_code}.favorite DESC ";
            }else{
                $sql = " favorite DESC ";
            }
        }elseif( $sort_by == 4 ){//浏览
            if( $table_code ){
                $sql = " {$table_code}.browse DESC ";
            }else{
                $sql = " browse DESC ";
            }
        }elseif( $sort_by == 5 ){//喜欢、点赞
            if( $table_code ){
                $sql = " {$table_code}.praise DESC ";
            }else{
                $sql = " praise DESC ";
            }
        }elseif( $sort_by == 6 ){//提交、修改、删除时间排序
            if( $table_code ){
                $sql = " {$table_code}.update_time {$sort_type_str} ";
            }else{
                $sql = " update_time {$sort_type_str} ";
            }
        }elseif( $sort_by == 7 ){//上传时间
            if( $table_code ){
                $sql = " {$table_code}.id {$sort_type_str} ";
            }else{
                $sql = " id {$sort_type_str} ";
            }
        }elseif( $sort_by == 8 ){//删除时间
            if( $table_code ){
                $sql = " {$table_code}.del_time {$sort_type_str} ";
            }else{
                $sql = " del_time {$sort_type_str} ";
            }
        }else{//默认排序
            if( $table_code ){
                $sql = " {$table_code}.favorite DESC, {$table_code}.download DESC, {$table_code}.audit_time DESC ";
            }else{
                $sql = " favorite DESC, download DESC, audit_time DESC ";
            }
        }
        if ($sort_by != 7) {
            //出现几个排序字段都相等的时候，查询分页出问题处理，增加id降序
            if( $table_code ){
                $sql .= " ,{$table_code}.id DESC ";
            }else{
                $sql .= " , id DESC ";
            }
        }

        return "ORDER BY ". $sql;
    }
    
    /*
     * 为你推荐
     */
    public static function getRecommends($params=[]){
        $eid = $params['eid'] ? $params['eid'] : [];//eid
        $limit = $params['limit'] ? $params['limit'] : 20;//数量限制-分页用
        unset($params['limit']);
        //获取eid图片信息
        $img_info = self::getOneImg($params);
        if( !$img_info ){
            return [
                'ret' => false,
                'msg' => '图片信息有误，请重试',
            ];
        }
        //获取推荐图片
        $keyword = $img_info['keyword'] ? $img_info['keyword'] : [];
        $small_type = $img_info['small_type'] ? $img_info['small_type'] : '';
        $city = $img_info['city'] ? $img_info['city'] : '';
        if( !$keyword && !$small_type && !$city ){
            return [
                'ret' => false,
                'msg' => '查询信息有误，请重试',
            ];
        }
        
        $sql_tmp = "  SELECT * , array_to_json(keyword) AS keyword_arr ";
        if( $keyword ){
            $keyword_str = implode("','", $keyword);
            $keyword_str2 = implode("|", $keyword);
            $sql_tmp.= " ,case when ( keyword::text[] && ARRAY['{$keyword_str}'] OR title ~'{$keyword_str2}' ) 
                THEN 1 ELSE 0 END AS keyword_tmp ";
        }else{
            $sql_tmp.= " ,1 AS keyword_tmp ";
        }
        
        if( $small_type ){
            $tmpSmallType = implode("','", json_decode($small_type, true));
            $sql_tmp.= " ,case when ( small_type::jsonb ?| array['$tmpSmallType']   ) 
                THEN 1 ELSE 0 END AS small_type_tmp ";
        }else{
            $sql_tmp.= " ,1 AS small_type_tmp ";
        }
        
        if( $city ){
            $sql_tmp.= " ,case when ( location->>'city' = '{$city}' ) 
                THEN 1 ELSE 0 END AS city_tmp ";
        }else{
            $sql_tmp.= " ,1 AS city_tmp ";
        }

        //去掉原有eid
        $sql_tmp.= " FROM ".QImgSearch::$imgTableName." WHERE eid != '{$eid}' AND is_del = 'f' AND audit_state = 2 ";
        $sql = " SELECT * FROM ({$sql_tmp}) tmp 
            ORDER BY keyword_tmp DESC, small_type_tmp DESC, city_tmp DESC, browse DESC LIMIT {$limit} ";

        
        //开始时间
        $sql_begin_time = microtime(true);
        
        //获取图片列表
        $db_rs = DBSLAVE::GetQueryResult($sql, false);
        
        
        //sql执行时间
        $sql_exec_time = sprintf("%.2f",microtime(true)-$sql_begin_time);
        $message = "图片搜索，sql如下：". $sql."；开始时间：".$sql_begin_time."；sql执行时间：".$sql_exec_time;
        QLog::info(self::$log_prefer,self::$log_action,$message);
        
        return [
            'ret' => true,
            'data' => $db_rs ? $db_rs : [],
        ];
    }

    public static function personalImgSimpleInfo($eid, $username)
    {
        global $system_domain;

        $eid = intval($eid);
        $username = (string) $username;

        $sql = <<<SQL
select i.id, i.eid, i.title, i.file_name, i.ext, i.url, i.logo_url, i.username, i.place, i.location, i.location->>'country' AS country, 
    i.location->>'province' AS province, i.location->>'city' AS city, i.location->>'county' AS county, i.small_type, i.purpose, i.filesize,
    i.width, i.height, i.keyword, i.upload_source, i.purchase_source, i.original_author, (case when i.is_signature is null then 'f' else i.is_signature end) as is_signature,
    i.audit_state, i.create_time as origin_create_time, to_char(i.create_time, 'YYYY-MM-DD HH24:MI:SS') as create_time, su.name as realname 
    from public.img i
    left join public.system_user su on i.username = su.username
    where i.eid = '{$eid}' and i.username = '{$username}' and i.big_type = 1 and i.is_del = 'f'
SQL;

        //图片详情
        $imgSimpleInfo = (array) DBSLAVE::GetQueryResult($sql);

        if (! empty($imgSimpleInfo)) {
            $imgSimpleInfo["title"] = $imgSimpleInfo["title"] ? $imgSimpleInfo["title"] : basename($imgSimpleInfo['file_name'],strrchr($imgSimpleInfo['file_name'],"."));
            $imgSimpleInfo["url"] = QImg::getImgUrlResize([
                "img" => $imgSimpleInfo["url"],
                "width" => $imgSimpleInfo["width"],
                "height" => $imgSimpleInfo["height"],
                "r_width" => 500,
                "r_height" => 500,
                'system_domain' => $system_domain,
                "in" => "out_domain"
            ]);
            $imgSimpleInfo["logo_url"] = $imgSimpleInfo["logo_url"] ? QImg::getImgUrlResize([
                "img" => $imgSimpleInfo["logo_url"],
                "width" => $imgSimpleInfo["width"],
                "height" => $imgSimpleInfo["height"],
                "r_width" => 500,
                "r_height" => 500,
                'system_domain' => $system_domain,
                "in" => "out_domain"
            ]) : '';
            $imgSimpleInfo["keyword"] = pgarray2array($imgSimpleInfo["keyword"]);
            $imgSimpleInfo["location"] = array_values(array_filter([
                $imgSimpleInfo['country'],
                $imgSimpleInfo['province'],
                $imgSimpleInfo['city'],
                $imgSimpleInfo['county'],
                $imgSimpleInfo['place']
            ]));
            $imgSimpleInfo["small_type"] = json_decode($imgSimpleInfo["small_type"], true) ?: [];
            $imgSimpleInfo["upload_source"] = (string) $imgSimpleInfo["upload_source"];
            $imgSimpleInfo["purchase_source"] = (string) $imgSimpleInfo["purchase_source"];
            $imgSimpleInfo["original_author"] = (string) $imgSimpleInfo["original_author"];
            $imgSimpleInfo["is_signature"] = $imgSimpleInfo["is_signature"] == 't';
            $imgSimpleInfo["filesize_convert"] = byte_convert($imgSimpleInfo["filesize"]);
            $imgSimpleInfo["place"] = (string) $imgSimpleInfo["place"];
            $imgSimpleInfo["uploader_username"] = (string) $imgSimpleInfo["username"];
            $imgSimpleInfo["uploader_realname"] = (string) $imgSimpleInfo["realname"];
        }

        return array_only($imgSimpleInfo, [
            "id", "eid", "title", "url", "logo_url", "username", "place", "location", "small_type", "purpose",
            "filesize_convert", "keyword", "upload_source", "purchase_source", "original_author", "is_signature",
            "audit_state", "uploader_username", "uploader_realname", "create_time"
        ]);
    }
}
