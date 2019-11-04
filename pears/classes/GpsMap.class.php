<?php

/**
 * gps 转换相关
 *
 * http://maps.google.com.tw/?q=31 49 35,117 13 20.7
 * http://maps.google.com.tw/?q=39 58 34.8563,116 17 59.1828 //图片的维亚
 *
 * WGS-84：是国际标准，GPS坐标（Google Earth使用、或者GPS模块）
 * GCJ-02：中国坐标偏移标准，Google Map、高德、腾讯使用
 * BD-09：百度坐标偏移标准，Baidu Map使用
 * http://www.gzhatu.com/jingweidu.html 百度坐标获取经纬度
 * http://www.gzhatu.com/dingwei.html wgs-84 经纬度定位
 * 本类来源于以下blog
 * https://blog.csdn.net/u014263216/article/details/53424627
 *  http://www.gpsspg.com/maps.htm 各种查gps 位置
 *
 *
$test = new GpsMap();//

//lng lat 纬度 经度 维亚大厦 转google坐标
var_dump($test->wgs84togcj02(39.977611812448,116.30585503064));
39.977611812448,116.30585503064
 */

class GpsMap
{
    const x_PI  = 52.35987755982988;
    const PI  = 3.1415926535897932384626;
    const a = 6378245.0;
    const ee = 0.00669342162296594323;


    /**
     * WGS84转GCj02(北斗转高德)
     * @param lng
     * @param lat
     * @returns {*[]}
     */
    public function wgs84togcj02($lng,  $lat) {
        if ($this->out_of_china($lng, $lat)) {
            return array($lng, $lat);
        } else {
            $dlat = $this->transformlat($lng - 105.0, $lat - 35.0);
            $dlng = $this->transformlng($lng - 105.0, $lat - 35.0);
            $radlat = $lat / 180.0 * self::PI;
            $magic = sin($radlat);
            $magic = 1 - self::ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / ((self::a * (1 - self::ee)) / ($magic * $sqrtmagic) * self::PI);
            $dlng = ($dlng * 180.0) / (self::a / $sqrtmagic * cos($radlat) * self::PI);
            $mglat = $lat + $dlat;
            $mglng = $lng + $dlng;
            return array($mglng, $mglat);
        }
    }
    /**
     * GCJ02 转换为 WGS84 (高德转北斗)
     * @param lng
     * @param lat
     * @return array(lng, lat);
     */
    public function gcj02towgs84($lng, $lat) {
        if ($this->out_of_china($lng, $lat)) {
            return array($lng, $lat);
        } else {
            $dlat = $this->transformlat($lng - 105.0, $lat - 35.0);
            $dlng = $this->transformlng($lng - 105.0, $lat - 35.0);
            $radlat = $lat / 180.0 * self::PI;
            $magic = sin($radlat);
            $magic = 1 - self::ee * $magic * $magic;
            $sqrtmagic = sqrt($magic);
            $dlat = ($dlat * 180.0) / ((self::a * (1 - self::ee)) / ($magic * $sqrtmagic) * self::PI);
            $dlng = ($dlng * 180.0) / (self::a / $sqrtmagic * cos($radlat) * self::PI);
            $mglat = $lat + $dlat;
            $mglng = $lng + $dlng;
            return array($lng * 2 - $mglng, $lat * 2 - $mglat);
        }
    }


        /**
    　　* 百度坐标系 (BD-09) 与 火星坐标系 (GCJ-02)的转换
    　　* 即 百度 转 谷歌、高德
    　　* @param bd_lon
    　　* @param bd_lat
    　　* @returns
　　*/
        public function bd09togcj02 ($bd_lon, $bd_lat) {
            $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
            $x = $bd_lon - 0.0065;
            $y = $bd_lat - 0.006;
            $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
            $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
            $gg_lng = $z * cos(theta);
            $gg_lat = $z * sin(theta);
            return array($gg_lng, $gg_lat);
        }

    /**
    * GCJ-02 转换为 BD-09  （火星坐标系 转百度即谷歌、高德 转 百度）
    * @param $lng
    * @param $lat
    * @returns array(bd_lng, bd_lat)
    */
    public function gcj02tobd09($lng, $lat) {
        $z = sqrt($lng * $lng + $lat * $lat) + 0.00002 * Math.sin($lat * x_PI);
        $theta = Math.atan2($lat, $lng) + 0.000003 * Math.cos($lng * x_PI);
        $bd_lng = $z * cos($theta) + 0.0065;
        $bd_lat = z * sin($theta) + 0.006;
        return array($bd_lng, $bd_lat);
    }


    private function transformlat($lng, $lat) {
        $ret = -100.0 + 2.0 * $lng + 3.0 * $lat + 0.2 * $lat * $lat + 0.1 * $lng * $lat + 0.2 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * self::PI) + 20.0 * sin(2.0 * $lng * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lat * self::PI) + 40.0 * sin($lat / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (160.0 * sin($lat / 12.0 * self::PI) + 320 * sin($lat * self::PI / 30.0)) * 2.0 / 3.0;
        return $ret;
    }
    private function transformlng($lng, $lat) {
        $ret = 300.0 + $lng + 2.0 * $lat + 0.1 * $lng * $lng + 0.1 * $lng * $lat + 0.1 * sqrt(abs($lng));
        $ret += (20.0 * sin(6.0 * $lng * self::PI) + 20.0 * sin(2.0 * $lng * self::PI)) * 2.0 / 3.0;
        $ret += (20.0 * sin($lng * self::PI) + 40.0 * sin($lng / 3.0 * self::PI)) * 2.0 / 3.0;
        $ret += (150.0 * sin($lng / 12.0 * self::PI) + 300.0 * sin($lng / 30.0 * self::PI)) * 2.0 / 3.0;
        return $ret;
    }


    private function rad($param)
    {
      return  $param * self::PI / 180.0;
    }
    /**
    * 判断是否在国内，不在国内则不做偏移
    * @param $lng
    * @param $lat
    * @returns {boolean}
    */
    private function out_of_china($lng, $lat) {
        return ($lng < 72.004 || $lng > 137.8347) || (($lat < 0.8293 || $lat > 55.8271) || false);
    }

}
