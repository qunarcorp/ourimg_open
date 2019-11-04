<?php

/**
 *
 */

class Utility {

    const CHAR_MIX = 0;
    const CHAR_NUM = 1;
    const CHAR_WORD = 2;

    static public function GetRemoteIp($default = '127.0.0.1') {
        $ip_string = $_SERVER['HTTP_CLIENT_IP'] . ',' . $_SERVER['HTTP_X_FORWARDED_FOR'] . ',' . $_SERVER['REMOTE_ADDR'];
        if (preg_match("/\d+\.\d+\.\d+\.\d+/", $ip_string, $matches)) {//$matches为搜索结果
            return $matches[0];
        }
        return $default;
    }

    static public function OptionArray($a = array(), $c1, $c2) {
        if (empty($a))
            return array();
        $s1 = self::GetColumn($a, $c1);
        $s2 = self::GetColumn($a, $c2);
        if ($s1 && $s2 && count($s1) == count($s2)) {
            return array_combine($s1, $s2);
        }
        return array();
    }

    static public function GetColumn($a = array(), $column = 'id', $null = true, $column2 = null) {
        $ret = array();
        @list($column, $anc) = preg_split('/[\s\-]/', $column, 2, PREG_SPLIT_NO_EMPTY);
        foreach ($a AS $one) {
            if ($null || @$one[$column])
                $ret[] = @$one[$column] . ($anc ? '-' . @$one[$anc] : '');
        }
        return $ret;
    }

    /* support 2-level now */

    static public function AssColumn($a = array(), $column = 'id') {
        $two_level = func_num_args() > 2 ? true : false;
        if ($two_level)
            $scolumn = func_get_arg(2);

        $ret = array();
        settype($a, 'array');
        if (false == $two_level) {
            foreach ($a AS $one) {
                if (is_array($one))
                    $ret[@$one[$column]] = $one;
                else
                    $ret[@$one->$column] = $one;
            }
        }
        else {
            foreach ($a AS $one) {
                if (is_array($one)) {
                    if (false == isset($ret[@$one[$column]])) {
                        $ret[@$one[$column]] = array();
                    }
                    $ret[@$one[$column]][@$one[$scolumn]] = $one;
                } else {
                    if (false == isset($ret[@$one->$column]))
                        $ret[@$one->$column] = array();

                    $ret[@$one->$column][@$one->$scolumn] = $one;
                }
            }
        }
        return $ret;
    }


    /**
     * 优化HttpRequest函数，由于需要更多的参数，优化参数为数组类型，也建议以后使用数组类型的参数
     * @param type $options
     * @return type
     */
    static function GetHttpRequest($options = array()) {
        $url = !empty($options['url']) ? strval($options['url']) : '';
        $data = !empty($options['data']) ? (is_array($options['data']) ? $options['data'] : array()) : array();
        $timeout = is_numeric($options['timeout']) &&$options['timeout']>0  ? $options['timeout'] : 1;
//        $log = !empty($options['log']) ? boolval($options['log']) : false; 
        $cookie = !empty($options['cookie']) ? strval($options['cookie']) : '';
        $log_prefix = !empty($options['log_prefix']) ? strval($options['log_prefix']) : '';
        $log_action = !empty($options['log_action']) ? strval($options['log_action']) : '';
        $log_type = !empty($options['log_type']) ? strval($options['log_type']) : 'all';

        $api_begin_time = microtime(true);
        $ch = curl_init();
        if($cookie){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($options['setcookie']) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if (is_array($data) && $data) {
            if ($options['post_json']) {//支持post json格式
                $json_options = !empty($options['json_options']) ? $options['json_options'] : '';
                if ($json_options) {
                    $formdata = json_encode($data, $json_options);
                } else {
                    $formdata = json_encode($data);
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
            } elseif ($options['post_formdata']) { //支持form data
                $formdata = $data;
            } else {
                $formdata = http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);
        }else{
            $headers = array(
                "Cache-Control: no-cache",
            );
            if(array_key_exists("header_accept", $options)){
                array_push($headers, $options['header_accept']);
            }
            if($headers){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
        }
        if(strtolower(substr($url, 0,8) =='https://')){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在 
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, ($timeout * 1000));
        $result = curl_exec($ch);
        ###echo "curl -i -X POST -d '{$formdata}' {$options['url']}";
        if ($log_prefix && $log_action && $log_type != 'watcher') {
            $curl_info = curl_getinfo($ch);

            $log_info = array();
            $log_info['request_time'] = (sprintf("%.3f",$curl_info['total_time']))*1000; //ms
            $log_info['error_msg'] = curl_error($ch);
            $log_info['param'] = $options;
            $log_info['url'] = $url;

            if (in_array($log_type, array("all", "http_code"))) {
                $http_code = $curl_info['http_code'];
                if ($http_code == 0) {
                    $http_code = curl_errno($ch);
                    if ($http_code == 28) {
                        //curl超时
                        $http_code = 504;
                    }
                }
                $log_info['http_code'] = $http_code;
            }
            if (in_array($log_type, array("all"))) {
                $log_info['result'] =$result ;
            }

            QLog::info($log_prefix, $log_action, json_encode($log_info,JSON_UNESCAPED_UNICODE));
        }else  if($log_type == 'watcher') {
            $watcher_log = [];
            $curl_info = curl_getinfo($ch);
            $curl_erno = curl_errno($ch);
            $watcher_log['watcher_name'] = $log_action ? $log_action : parse_url($url)['path'];
            if($curl_erno) {
                $watcher_log['http_code'] = ($curl_erno == 28) ? 504 : $curl_info['http_code'];
                $watcher_log['request_time'] = 0;
                $watcher_log['error_msg'] = curl_error($ch);
            } else {
                $watcher_log['http_code'] = $curl_info['http_code'];
                $watcher_log['request_time'] = round($curl_info['total_time'], 4);
                $watcher_log['error_msg'] = '';
            }
            
            QLog::info('watcher_access', 'log', ' '.implode("--", $watcher_log));
        }

        curl_close($ch);

        return $result;
    }
    
    /**
     * 优化HttpRequest函数，由于需要更多的参数，优化参数为数组类型，也建议以后使用数组类型的参数
     * @param type $options
     * @return type
     */
    static function GetHttpRequestOnlyCurl($options = array()) {
        $url = !empty($options['url']) ? strval($options['url']) : '';
        $data = !empty($options['data']) ? (is_array($options['data']) ? $options['data'] : array()) : array();
        $timeout = is_numeric($options['timeout']) &&$options['timeout']>0  ? $options['timeout'] : 1;
        $cookie = !empty($options['cookie']) ? strval($options['cookie']) : '';
        $log_prefix = !empty($options['log_prefix']) ? strval($options['log_prefix']) : '';
        $log_action = !empty($options['log_action']) ? strval($options['log_action']) : '';
        $log_type = !empty($options['log_type']) ? strval($options['log_type']) : 'all';

        $api_begin_time = microtime(true);
        $ch = curl_init();
        if($cookie){
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($options['setcookie']) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if (is_array($data) && $data) {
	        if ($options['post_json']) {//支持post json格式
		        $json_options = !empty($options['json_options']) ? $options['json_options'] : '';
		        if ($json_options) {
			        $formdata = json_encode($data, $json_options);
		        } else {
			        $formdata = json_encode($data);
		        }
		        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8'));
	        } elseif ($options['post_formdata']) { //支持form data
		        $formdata = $data;
	        } else {
		        $formdata = http_build_query($data);
	        }
			curl_setopt($ch, CURLOPT_POST, true);
	        
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $formdata);
	        if ($options['upload_file']) {
		        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));

		        curl_setopt($ch, CURLOPT_POSTFIELDS, $options['uploadfile']); // post images
            }
        }else{
            $headers = array(
                "Cache-Control: no-cache",
            );
            if(array_key_exists("header_accept", $options)){
                array_push($headers, $options['header_accept']);
            }
            if($options['headers']){
                foreach($options['headers'] as $hv){
                    array_push($headers, $hv);
                }
            }
            if($headers){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
        }
        if(strtolower(substr($url, 0,8) =='https://')){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true); // 从证书中检查SSL加密算法是否存在 
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, ($timeout * 1000));

        $result = curl_exec($ch);

        $curl_info = curl_getinfo($ch);
        $http_code = $curl_info['http_code'];
        if ($http_code == 0) {
            $http_code = curl_errno($ch);
            if ($http_code == 28) {
                //curl超时
                $http_code = 504;
            }
        }
        if ($log_prefix && $log_action) {
            $log_info = array();
            $log_info['request_time'] = (sprintf("%.3f",$curl_info['total_time']))*1000; //ms
            $log_info['error_msg'] = curl_error($ch);
            $log_info['param'] = $options;
            $log_info['url'] = $url;

            if (in_array($log_type, array("all", "http_code"))) {
                $log_info['http_code'] = $http_code;
            }
            if (in_array($log_type, array("all"))) {
                $log_info['result'] =$result ;
            }

            QLog::info($log_prefix, $log_action, json_encode($log_info,JSON_UNESCAPED_UNICODE));
        }

        curl_close($ch);

        return array("http_code"=>$http_code,'result'=>$result);
    }
    
    //生成随机数
    static function GenSecret($len = 6, $type = self::CHAR_WORD) {
        $secret = '';
        for ($i = 0; $i < $len; $i++) {
            mt_srand();
            if (self::CHAR_NUM == $type) {
                if (0 == $i) {
                    $secret .= chr(mt_rand(49, 57));
                } else {
                    $secret .= chr(mt_rand(48, 57));
                }
            } else if (self::CHAR_WORD == $type) {
                $secret .= chr(mt_rand(65, 90));
            } else {
                if (0 == $i) {
                    $secret .= chr(mt_rand(65, 90));
                } else {
                    $secret .= (0 == mt_rand(0, 1)) ? chr(mt_rand(65, 90)) : chr(mt_rand(48, 57));
                }
            }
        }
        return $secret;
    }
    
    /**
     *  加密手机号
     * @param string $mobile
     * @return  string ||false 
     */
    static function encryptMobile($mobile){
        return $mobile;
    }
    /**
     *  解密手机号
     * @param string $mobile
     * @return  string ||false 
     */
    static function decryptMobile($mobile){
        return $mobile;
    }
}
