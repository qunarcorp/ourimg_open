<?php

/**
 * gps定位城市 ，
 * 获取城市具体信息
 */

class QSight{
    /**
     * 攻略根据gps 定位city接口 取度假city_id 以及city名称
     * @var string
     */
    private static $locate_api = "https://hy.travel.qunar.com/api/locate/locate_near";
    /**
     * 城市搜索 suggest sight
     * @var string
     */
    private static $dujia_sight_suggest = "http://sgt.package.qunar.com/suggest/sight/sgt";

    /**
     * 城市poi详细
     * @var string
     */
    private static $dujia_sight_info = "http://sgt.package.qunar.com/suggest/sight/info";

    /**
     * google  经纬
     * @param $lng 经度
     * @param $lat 纬度
     *
     */
    public static function gps_to_city($lng,$lat,$cityOnly=1)
    {
        global $INI;
        $data = array();
        $data["bd_source"] = $INI['system']['app_code'];
        $data["cityOnly"] = $cityOnly;
        $data["coord_type"] = 1;
        $data["needNear"] = 0;
        $data["lng"] = $lng;
        $data["lat"] = $lat;

        $options = array();
        $options['url'] = static::$locate_api . "?" . http_build_query($data);
        $options['log_prefix'] = "outapi";
        $options['log_action'] = "qsight_gps_to_city";
        $options['timeout'] = 2;

        $rs = Utility::GetHttpRequestOnlyCurl($options);
        $city_info = array();
        if (200 == $rs['http_code']) {
            $result = json_decode($rs['result'], true);
            $city_info = $result['data']['citys']['dujia'];
        }
        return $city_info;
    }

    /**
     * 获取sight信息,不支持英文
     * @param $sight_id
     * @return array|mixed
     *
     * {"ret":true,"data":{"country":"越南","parentName":"庆和省","address":"越南中部沿海","pinyin":"yazhuangshi","province":"庆和省","name":"芽庄","id":32412,"type":"城市"}}
     */
    public static function sight_info($sight_id){
        //?sightId=32412
        $data = array();
        $data["sightId"] = $sight_id;
        $data["flMore"] = "parent_names,parent_types,parents";

        $options = array();
        $options['url'] = static::$dujia_sight_info . "?" . http_build_query($data);
        $options['log_prefix'] = "outapi";
        $options['log_action'] = "qsight_sight_info";
        $options['timeout'] = 2;

        $result = api_request_cache_common($options,86400);
        return $result['data'];
    }
    /**
     *  城市 suggest 前端接口可以直接使用，可不用二次封装
     * @param $query
     * @return array
     */
    public static function city_suggest($query){
        $data = array();
        $data["isContain"] = true;
        $data["flMore"] = "";//
        $data["type"] = "区县,国家,地区,城市,景区,景点,省份";
        $data["query"] = $query;

        $options = array();
        $options['url'] = static::$dujia_sight_suggest . "?" . http_build_query($data);
        $options['log_prefix'] = "outapi";
        $options['log_action'] = "qsight_city_suggest";
        $options['timeout'] = 2;

        return api_request_cache_common($options,86400);
    }

    /**
     * 获取city的ltree 格式
     * @param $sight_info
     * @return string
     * 格式中国.河北省.保定市.涿州市
     */
    public static function sight_city_ltree($sight_info){

        $parent_types = explode(" ",$sight_info['parent_types']);
        $parent_names = explode(" ",$sight_info['parent_names']);

        //"景区 区县 城市 省份 地区 国家 大洲"
        $country    = "_";//国家
        $province   = "_";//省
        $city       = "_";//城市
        $county     = "_";//区县
        foreach($parent_types as $k=>$val){
            switch ($val){
                case "国家":
                    $country = $parent_names[$k];
                    break;
                case "省份":
                    $province = $parent_names[$k];
                    break;
                case "城市":
                    $city = $parent_names[$k];
                    break;
                case "区县":
                    $county = $parent_names[$k];
                    break;
                default:
            }
        }

        switch ($sight_info['type']){
            case "国家":
                $country = $sight_info["name"];
                break;
            case "省份":
                $province = $sight_info["name"];
                break;
            case "城市":
                $city = $sight_info["name"];
                break;
            case "区县":
                $county = $sight_info["name"];
                break;
            default:
        }

        if($city!='_' && $province=='_'){
            $province = $city;
        }
        $city_ltree = "{$country}.{$province}.{$city}.{$county}";
        return $city_ltree;
    }
    
    /**
     * 获取city的ltree 格式--修改为jsonb类型，由于ltree不支持空格和特殊字符
     * @param $sight_info
     * @return string
     * 格式中国.河北省.保定市.涿州市
     */
    public static function sight_city_json($sight_info){

        $parent_types = explode(" ",$sight_info['parent_types']);
        $parent_names = explode(" ",$sight_info['parent_names']);

        //"景区 区县 城市 省份 地区 国家 大洲"
        $country    = "";//国家
        $province   = "";//省
        $city       = "";//城市
        $county     = "";//区县
        foreach($parent_types as $k=>$val){
            switch ($val){
                case "国家":
                    $country = $parent_names[$k];
                    break;
                case "省份":
                    $province = $parent_names[$k];
                    break;
                case "城市":
                    $city = $parent_names[$k];
                    break;
                case "区县":
                    $county = $parent_names[$k];
                    break;
                default:
            }
        }

        switch ($sight_info['type']){
            case "国家":
                $country = $sight_info["name"];
                break;
            case "省份":
                $province = $sight_info["name"];
                break;
            case "城市":
                $city = $sight_info["name"];
                break;
            case "区县":
                $county = $sight_info["name"];
                break;
            default:
        }

        if($city && !$province){
            $province = $city;
        }
        $city_arr = [
            'country' => $country,
            'province' => $province,
            'city' => $city,
            'county' => $county,
        ];
        return json_encode($city_arr, JSON_UNESCAPED_UNICODE);
    }

}
