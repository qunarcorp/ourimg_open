<?php


/**
 * 图片gps 信息转换
 */


class QImgGps{
    /**
     * 从图片原始的exif gps信息转换成度数 ，如113度12分39秒 113.211°
     * @param $GPSLongitude   exif ['GPS']['GPSLongitude'] 经度数组
     * @param $GPSLongitudeRef    exif ['GPS']['GPSLongitudeRef'] W E
     * @param $GPSLatitude        exif ['GPS']['GPSLatitude']  纬度数组
     * @param $GPSLatitudeRef     exif ['GPS']['GPSLongitudeRef'] S N
     * @return array
     * //东经为E 英文东边east  $GPSLongitudeRef W E
     * //西经为W 英文西边west
     * //南纬为S 英文南方south $GPSLatitudeRef S N
     * //北纬为N 英文北方north
     *
    $img_extif = exif_read_data( "gps2.jpg","ANY_TAG",true );

    $gps_array = QImgGps::imgGpsToLngLat($img_extif['GPS']['GPSLongitude'],$img_extif['GPS']['GPSLongitudeRef'],$img_extif['GPS']['GPSLatitude'],$img_extif['GPS']['GPSLatitudeRef']);

     */

    public static function imgGpsToLngLat($GPSLongitude,$GPSLongitudeRef,$GPSLatitude,$GPSLatitudeRef){
        return array("lng"=>($GPSLongitudeRef=="W"?-1:1) * static ::getGps($GPSLongitude),"lat"=>($GPSLatitudeRef=="S"?-1:1) *static ::getGps($GPSLatitude));
    }
    /**
     * 图片的度分秒转换成 经纬度
     * @param $exif_gps   39 58 34.8563  39.976348972222
     * @return float|int
     */
    private static function getGps($exif_gps){
        $count = count($exif_gps);
        $degrees= $count > 0 ? static ::gps2Num($exif_gps[0]) : 0;
        $minutes= $count > 1 ? static ::gps2Num($exif_gps[1]) : 0;
        $seconds= $count > 2 ? static ::gps2Num($exif_gps[2]) : 0;

        $minutes+= 60 * ($degrees- floor($degrees));
        $degrees= floor($degrees);
        $seconds+= 60 * ($minutes- floor($minutes));
        $minutes= floor($minutes);

        if($seconds>= 60){
            $minutes+= floor($seconds/60.0);
            $seconds-= 60*floor($seconds/60.0);
        }
        if($minutes>= 60){
            $degrees+= floor($minutes/60.0);
            $minutes-= 60*floor($minutes/60.0);
        }
        //获取gps的度分秒,组合成经纬度
        return ($degrees + ($minutes + $seconds/60)/60);
    }

    private static function gps2Num($gps){
        $parts= explode('/', $gps);
        if(count($parts) <= 0) {
            return 0;
        }

        if(count($parts) == 1){
            return $parts[0];
        }

        return floatval($parts[0]) / floatval($parts[1]);
    }

}
