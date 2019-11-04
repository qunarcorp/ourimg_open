<?php

/**
 * 个人中心查询和操作
 */

class QImgPersonal {
    /*
     * 用户进入个人中心，获取用户信息
     */
    public static function getUserInfo($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        if( !$username ){
            return false;
        }
        
        $sql = " SELECT * FROM ". QImgSearch::$userTableName." WHERE username = '{$username}' ";
        $userinfo = DBSLAVE::GetQueryResult($sql);
        
        //今日积分
        $last_date_points = "+0";
        if( $userinfo['last_point_date'] == date("Y-m-d") ){
            if( $userinfo['last_date_points'] > 0 ){
                $last_date_points = "+".$userinfo['last_date_points'];
            }elseif( $userinfo['last_date_points'] < 0 ){
                $last_date_points = $userinfo['last_date_points'];
            }
        }
        
        $userinfo_rs = [
            'user_img' => $userinfo['img'],
            'points_info' => [
                'total_points' => $userinfo['total_points'] > 0 ? $userinfo['total_points'] : '0',
                'current_points' => $userinfo['current_points'] > 0 ? $userinfo['current_points'] : '0',
                'last_date_points' => $last_date_points,
            ],
            'role' => array_merge(json_decode($userinfo['role'],true), QAuth::isSuperAdmin() ? ['super_admin'] : []),
            'username' => $userinfo['username'],
            'name' => $userinfo['name'],
            'auth_state' => $userinfo['auth_state'] ? $userinfo['auth_state'] : '0',
            'auth_date' => $userinfo['auth_time'] ? date("Y-m-d", strtotime($userinfo['auth_time'])) : null,
            'dept' => json_decode($userinfo['dept'],true),
        ];
        
        return $userinfo_rs ? $userinfo_rs : [];
    }
    
    /*
     * 生成外网链接
     */
    public static function getImgUrl($params=[]){
        global $system_domain;
        $url = $params['url'] ? $params['url'] : "";
        $width = $params['width'] ? $params['width'] : 0;
        $origin_width = $params['origin_width'] ? $params['origin_width'] : 0;
        $height = $params['height'] ? $params['height'] : 0;
        $origin_height = $params['origin_height'] ? $params['origin_height'] : 0;
        if( !$url ){
            return false;
        }
        
        return QImg::getImgUrlResize(
                array(
                    "img"=>$url,
                    "width"=>$origin_width,
                    "height"=>$origin_height,
                    "r_width"=>$width,
                    "r_height"=>$height,
                    'system_domain'=>$system_domain,
                    "in"=>"out_domain",
                    )
                );//图片上传的缩略图地址或者是缩略图
    }
    
    /*
     * 获取我的素材被点赞、被下载、被收藏、被浏览的数量
     */
    public static function getMyImgSum($params=[]){
        $username = $params['username'] ? $params['username'] : "";
        if( !$username ){
            return false;
        }
        
        $sql = " SELECT SUM(download) AS download_count,
                 SUM(favorite) AS favorite_count, 
                 SUM(praise) AS praise_count, 
                 SUM(browse) AS browse_count FROM "
                .QImgSearch::$imgTableName
                ." WHERE username = '{$username}' 
                 AND is_del = 'f' AND audit_state = 2 ";
        $db_rs = DB::GetQueryResult($sql);
        $count_rs = [
            'download_count' => $db_rs['download_count'] > 0 ? $db_rs['download_count'] : '0',
            'favorite_count' => $db_rs['favorite_count'] > 0 ? $db_rs['favorite_count'] : '0',
            'praise_count' => $db_rs['praise_count'] > 0 ? $db_rs['praise_count'] : '0',
            'browse_count' => $db_rs['browse_count'] > 0 ? $db_rs['browse_count'] : '0',
        ];
        
        return $count_rs;
    }

    public static function getDeptImgSum($params=[]){
        $uploadSource = $params['upload_source'] ? $params['upload_source'] : "";
        if( !$uploadSource ){
            return false;
        }

        $sql = " SELECT SUM(download) AS download_count,
                 SUM(favorite) AS favorite_count, 
                 SUM(praise) AS praise_count, 
                 SUM(browse) AS browse_count FROM "
            .QImgSearch::$imgTableName
            ." WHERE upload_source = '{$uploadSource}' 
                 AND is_del = 'f' AND audit_state = 2 ";
        $db_rs = DB::GetQueryResult($sql);
        $count_rs = [
            'download_count' => $db_rs['download_count'] > 0 ? $db_rs['download_count'] : '0',
            'favorite_count' => $db_rs['favorite_count'] > 0 ? $db_rs['favorite_count'] : '0',
            'praise_count' => $db_rs['praise_count'] > 0 ? $db_rs['praise_count'] : '0',
            'browse_count' => $db_rs['browse_count'] > 0 ? $db_rs['browse_count'] : '0',
        ];

        return $count_rs;
    }
    
    /*
     * 获取时间筛选项
     */
    public static function getTimeInfo($params=[]){
        $time_id = $params['time_id'] ? $params['time_id'] : "";
        if( !$time_id ){
            return false;
        }
        
        global $dic_img;
        $begin_time = '';
        if( $time_id == 1 ){//一周内下载
            $begin_time = date("Y-m-d H:i:s", strtotime("-7 day"));
        }elseif( $time_id == 2 ){//一个月内下载
            $begin_time = date("Y-m-d H:i:s", strtotime("-1 month"));
        }elseif( $time_id == 3 ){//三个月内下载
            $begin_time = date("Y-m-d H:i:s", strtotime("-3 month"));
        }elseif( $time_id == 4 ){//半年内下载
            $begin_time = date("Y-m-d H:i:s", strtotime("-6 month"));
        }elseif( $time_id == 5 ){//一年内下载
            $begin_time = date("Y-m-d H:i:s", strtotime("-1 year"));
        }
        return $begin_time;
    }
    
    /*
     * 获取sql-where
     */
    public static function getJoinSqlWhere($params=[]){
        $keyword = $params['keyword'] ? $params['keyword'] : '';//关键字
        $username = $params['username'] ? $params['username'] : '';//用户名
        $time_id = $params['time_id'] ? $params['time_id'] : '';//时间筛选
        $big_type = $params['big_type'] ? $params['big_type'] : 1;//大类型筛选
        $operate_type = $params['operate_type'] ? $params['operate_type'] : '';//操作类型

        $sql_where = " WHERE 1 = 1 ";
        $sql_where.= " AND i.audit_state = '2' ";
        if( $username ){
            $sql_where.= " AND f.username = '{$username}' ";
        }
        
        if( $keyword && is_array($keyword) ){
            $keyword_str = implode("','", $keyword);
            $sql_where.= " AND ( i.keyword::text[] && ARRAY['".$keyword_str."'] ";
            
            $keyword_str2 = implode("|", $keyword);
            $sql_where.= " OR i.title ~ '{$keyword_str2}' ";
            $sql_where.= " ) ";
        }
        
        $begin_time = self::getTimeInfo($params);
        if( $begin_time ){
            if( $operate_type == 'download' ){
                $sql_where.= " AND f.update_time >= '{$begin_time}' ";
            }else{
                $sql_where.= " AND f.create_time >= '{$begin_time}' ";
            }
        }
        
        //大分类
        if( $big_type ){
            $sql_where.= " AND i.big_type = '{$big_type}' ";
        }
        
        return $sql_where;
    }
    
    /*
     * 验证、判断用户登录
     */
    public static function checkUserLogin($params=[]){
        $callback = $params['callback'] ? $params['callback'] : '';//callback
        global $login_user_name;
        if(!$login_user_name){
            $rs = [
                "ret" => false,
                "msg" => "用户未登录",
                "data" => [],
                "count" => 0,
                "status" => 100,
            ];
            display_json_str_common($rs, $callback);
        }
    }
    /*
     * 验证、判断用户登录
     * 2019-01-14更新，修改审核接口的返回格式
     */
    public static function checkUserLoginNew($params=[]){
        $callback = $params['callback'] ? $params['callback'] : '';//callback
        if( !$callback ){
            $callback = $_GET['callback'] ? $_GET['callback'] : '';
        }
        global $login_user_name;
        if(!$login_user_name){
            $rs = [
                "status" => 100,
                "message" => "用户未登录",
                "data" => [],
                "count" => 0,
            ];
            display_json_str_common($rs, $callback);
        }
    }
}
