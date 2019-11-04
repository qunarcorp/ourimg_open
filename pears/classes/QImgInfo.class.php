<?php

/**
 * Class QImgInfo
 *
 * 图片是否喜欢，是否收藏 图片拍摄地点联动 图片扩展信息
 */

class QImgInfo {
    /*
     * 根据图片id查询用户是否点赞。喜欢
     */
    public static function getPraiseInfo($params=[], $params2=[]){
        $ids = $params['ids'] ? $params['ids'] : '';
        $username = $params2['username'] ? $params2['username'] : '';
        
        $sql_where = " WHERE 1 = 1 ";
        if( $ids && is_array($ids) ){
            $ids_str = implode("','", $ids);
            $sql_where.= " AND img_id IN ('{$ids_str}') ";
        }
        
        if( $username ){
            $sql_where.= " AND username = '{$username}' ";
        }
        
        $sql = " SELECT DISTINCT(img_id) FROM ". QImgSearch::$praiseTableName;
        $sql = $sql.$sql_where;
        $db_rs = DB::GetQueryResult($sql, false);
        if( !$db_rs || !is_array($db_rs) ){
            $db_rs = [];
        }
        return $db_rs;
    }
    
    /*
     * 根据图片id查询用户是否收藏
     */
    public static function getFavoriteInfo($params=[], $params2=[]){
        $ids = $params['ids'] ? $params['ids'] : '';
        $username = $params2['username'] ? $params2['username'] : '';
        
        $sql_where = " WHERE 1 = 1 ";
        if( $ids && is_array($ids) ){
            $ids_str = implode("','", $ids);
            $sql_where.= " AND img_id IN ('{$ids_str}') ";
        }
        
        if( $username ){
            $sql_where.= " AND username = '{$username}' ";
        }
        
        $sql = " SELECT DISTINCT(img_id) FROM ". QImgSearch::$favoriteTableName;
        $sql = $sql.$sql_where;
        $db_rs = DB::GetQueryResult($sql, false);
        if( !$db_rs || !is_array($db_rs) ){
            $db_rs = [];
        }
        return $db_rs;
    }
    
    /*
     * 拍摄地点的联动
     * 有其他筛选条件
     * country--province--city--address
     */
    public static function getLocations($params=[]){
        $country = $params['country'] ? $params['country'] : '';
        $province = $params['province'] ? $params['province'] : '';
        $city = $params['city'] ? $params['city'] : '';
        
        $sql_where = " where audit_state = 2 AND is_del = 'f' ";
        if( $country ){
            $sql_where.= " AND location->>'country' = '{$country}' ";
            if( $province ){
                $sql_where.= " AND location->>'province' = '{$province}' ";
                if( $city ){
                    $type = "address";
                    $location_sql = " SELECT DISTINCT(location->>'county') AS address FROM ". QImgSearch::$imgTableName;
                    $sql_where.= " AND location->>'city' = '{$city}' ";
                }else{//提供国家和省份信息，查询所有城市
                    $type = "city";
                    $location_sql = " SELECT DISTINCT(location->>'city') AS city FROM ".QImgSearch::$imgTableName;
                }
            }else{//只提供国家，查询国家下所有省份
                $type = "province";
                $location_sql = " SELECT DISTINCT(location->>'province') AS province FROM ".QImgSearch::$imgTableName;
            }
        }else{
            $type = "country";
            $location_sql = " SELECT DISTINCT(location->>'country') AS country FROM ".QImgSearch::$imgTableName;
        }
        
        $db_rs = DBSLAVE::GetQueryResult($location_sql.$sql_where,false);
        if( !$db_rs || !is_array($db_rs) ){
            return false;
        }else{
            $location_rs = array_unique(array_column($db_rs, $type));
            foreach( $location_rs as $k => $v ){
                if( !$v ){
                    unset($location_rs[$k]);
                }
            }
            return [
                'type' => $type,
                'info' => array_values($location_rs),
            ];
        }
    }
    
    
    //图片经纬度等exif整理格式
    public static function exifInfo($params=[]){
        //图片经纬度计算
        if( $params['extend'] ){
            $img_exif = json_decode($params['extend'], true);
            
            $ExposureProgram = array("未定义", "手动", "标准程序", "光圈先决", "快门先决", "景深先决", "运动模式", "肖像模式", "风景模式");
            $img_exif["detail"]["ColorSpace"] = $img_exif["detail"]["ColorSpace"] == 1 ? "sRGB" : "Uncalibrated";
            if( $img_exif["detail"]["FocalLength"] ){
                $img_exif["detail"]["FocalLength"] = $img_exif["detail"]["FocalLength"] . "mm";
            }
            $img_exif["detail"]["ExposureMode"] = $img_exif["detail"]["ExposureMode"] == 1 ? "手动" : "自动";
            $img_exif["detail"]["ExposureProgram"] = $ExposureProgram[$img_exif["detail"]["ExposureProgram"]];
            
            //经纬度
            if( $img_exif['extif']['GPS'] ){
                $GPSLatitude = $img_exif['extif']['GPS']['GPSLatitude'];
                if( $GPSLatitude ){
                    $GPSLatitude_new = '';
                    foreach( $GPSLatitude as $k => $v ){
                        $GPSLatitude_new_str = explode('/', $v);
                        if( $k == 0 ){
                            $GPSLatitude_new .= $GPSLatitude_new_str[0]."°";
                        }elseif( $k == 1 ){
                            $GPSLatitude_new .= $GPSLatitude_new_str[0]."′";
                        }else{
                            $GPSLatitude_new_second = round($GPSLatitude_new_str[0]/$GPSLatitude_new_str[1], 2);
                            $GPSLatitude_new .= $GPSLatitude_new_second."″";
                        }
                    }
                    $img_exif['extif']['GPS']['GPSLatitude_new'] = $GPSLatitude_new;
                }
                $GPSLongitude = $img_exif['extif']['GPS']['GPSLongitude'];
                if( $GPSLongitude ){
                    $GPSLongitude_new = '';
                    foreach( $GPSLongitude as $k => $v ){
                        $GPSLongitude_new_str = explode('/', $v);
                        if( $k == 0 ){
                            $GPSLongitude_new .= $GPSLongitude_new_str[0]."°";
                        }elseif( $k == 1 ){
                            $GPSLongitude_new .= $GPSLongitude_new_str[0]."′";
                        }else{
                            $GPSLongitude_new_second = round($GPSLongitude_new_str[0]/$GPSLongitude_new_str[1], 2);
                            $GPSLongitude_new .= $GPSLongitude_new_second."″";
                        }
                    }
                    $img_exif['extif']['GPS']['GPSLongitude_new'] = $GPSLongitude_new;
                }
            }
        }
        
        return $img_exif ? $img_exif : $params['extend'];
    }
}
