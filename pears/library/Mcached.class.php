<?php

class Mcached {
    
    public static function connect() {
        global $mcached_conn;
        global $INI;
        if (!$mcached_conn) {
            $mcached_conn = new Memcached();
            //测试三台分布式，挂掉两台，再从新开启这时二制制协议开启则get耗时严重，set速度不变
            $mcached_conn->setOption(Memcached::OPT_BINARY_PROTOCOL, true);
            $mcached_conn->setOption(Memcached::OPT_COMPRESSION, true);
            //超时时间
            $mcached_conn->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);//单位毫秒
            
            //$mcached_conn->setOption(Memcached::OPT_SEND_TIMEOUT, 200);//发送超时单位毫秒
            //$mcached_conn->setOption(Memcached::OPT_RECV_TIMEOUT, 100);//读取超时单位毫秒
            $mcached_conn->setOption(Memcached::OPT_POLL_TIMEOUT, 100);//投票超时单位毫秒

            //一致性算法 默认余数分布算法。
            $mcached_conn->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);//元素key的hash算法将会被设置为md5并且分布算法将会 采用带有权重的一致性hash分布
            $mcached_conn->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);//一致性分布算法(基于libketama).
            //故障转移设置
            /*
            $mcached_conn->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 1);
            $mcached_conn->setOption(Memcached::OPT_RETRY_TIMEOUT, 1);//失败重试单位秒
            $mcached_conn->setOption(Memcached::OPT_AUTO_EJECT_HOSTS, true);
            $mcached_conn->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS,true);//最新版本的故障转移
            */
            $mcached_conn->addServers($INI['db_config']['memcached']);
        }
        return $mcached_conn;
    }
    
    /**
     * 设置缓存
     * @param string $key 缓存key
     * @param string $value 缓存值
     * @param string $expire  缓存时间单位秒，默认3600秒
     * @return boolean 
     */
    public static function set($key, $value, $expire = 3600) {
        if(func_num_args() ==4){
            $expire = func_get_arg(3);//该判断仅用于支持cache类 
        }
        $mcached = self::connect();
        if ($mcached) {
            $ret = $mcached->set($key, $value, $expire);
            return $ret;
        } else {
            return false;
        }
    }
    /**
     * 获取缓存
     * @param string $key 缓存key
     * @return 缓存内容 
     */
    public static function get($key) {
        if (is_array($key)) {
            $v = array();
            foreach($key as $k) {
                $vv = self::getOne($k);
                if ($vv) { 
                    $v[$k] = $vv; 
                }
            }
            return $v;
        } else {
            return self::getOne($key);
        }
    }
    /**
     * 获取
     * @param string $key 缓存key
     * @return boolean
     */
    public static function getOne($key){
        $mcached = self::connect();
        if ($mcached) {
            $result = $mcached->get($key);
            if ($mcached->getResultCode() == Memcached::RES_NOTFOUND) {
                return false;
            }
            return $result;
        } else {
            return false;
        }
    }
    /**
     * 设置缓存
     * @param string $key 缓存key
     * @param string $value 缓存值
     * @param string $expire  缓存时间单位秒，默认3600秒
     * @return boolean 
     */
    public static function Add($key, $var,$expire=10) {
        if(func_num_args() ==4){
            $expire = func_get_arg(3);//该判断仅用于支持cache类 cache:: Add($key, $var, $flag=0, $expire=10) 
        }
        return self::set($key,$var,$expire);
    }
    /**
     * 设置缓存
     * @param string $key 缓存key
     * @param string $value 缓存值
     * @param string $expire  缓存时间单位秒，默认3600秒
     * @return boolean 
     */
    public static function Replace($key, $var,$expire=10){
        if(func_num_args() ==4){
            $expire = func_get_arg(3);//该判断仅用于支持cache类 cache:: Add($key, $var, $flag=0, $expire=10) 
        }
        return self::set($key,$var,$expire);
    }
    /**
     * 删除缓存
     * @param string $key  缓存key
     * @param integer $timeout  缓存值
     * @return boolean
     */
    public static function Del($key, $timeout=0) {
        $mcached = self::connect();
        if ($mcached) {
            if (is_array($key)) {
                foreach ($key as $k) { 
                    $mcached->delete($k, $timeout);
                }
            } else {
                $mcached->delete($key, $timeout);
            }
        }
        return true;
    }
    /**
     * 添除所有缓存
     * @param integer $delay 延时时间
     * @return type
     */
    public static function Flush($delay=0){
        $mcached = self::connect();
        if ($mcached) {
            return $mcached->flush($delay);
        }
    }
    /**
     * 获取函数缓存key
     * @param string $callback  函数名称
     * @param array $args      参数列表 
     * @return string
     */
    public static function GetFunctionKey($callback, $args=array()){
        $args = ksort($args);
        $patt = "/(=>)\s*'(\d+)'/";
        $args_string = var_export($args, true);
        $args_string = preg_replace($patt, "\\1\\2", $args_string);
        $key = "[FUNC]:$callback($args_string)";
        return self::GenKey( $key );
    }
    /**
    * 获取字符串key
    * @param type $str
    * @return type
    */
    public static function GetStringKey($str=null) {
        settype($str, 'array'); $str = var_export($str,true);
        $key = "[STR]:{$str}";
        return self::GenKey( $key );
    }
    /**
    * 获取对象key
    * @param string $tablename  表名
    * @param type $id    
    * @return string    
    */
    public static function GetObjectKey($tablename, $id){
        $key = "[OBJ]:$tablename($id)";
        return self::GenKey( $key );
    }
    /**
     * 生成key
     * @param type $key 
     * @return string
     */
    public static function GenKey($key) {
        $hash = dirname(__FILE__);
        return md5( $hash . $key );
    }

    public static function SetObject($tablename, $one) {
        foreach($one AS $oone) {
            $k = self::GetObjectKey($tablename, $oone['id']);
            self::set($k, $oone);
        }
        return true;
    }
    /**
    * 获取对象缓存的内容
    * @param string $tablename  表名
    * @param type $id    
    * @return array    
    */
    public static function GetObject($tablename, $id) {
        $single = ! is_array($id);
        settype($id, 'array');
        $k = array();
        foreach($id AS $oid) {
                $k[] = self::GetObjectKey($tablename, $oid);
        }
        $r = Utility::AssColumn(self::get($k), 'id');
        return $single ? array_pop($r) : $r;
    }
    /**
    * 删除对象缓存的内容 
    * @param string $tablename  表名
    * @param type $id    
    * @return array    
    */
    public static function ClearObject($tablename, $id) {
        settype($id, 'array');
        foreach($id AS $oid) {
            $key = self::GetObjectKey($tablename, $oid);
            self::del($key);
        }
        return true;
    }
    /**
     * 增加数值元素的值
     * @param string $key key值
     * @param intger $offset 要增加的值 
     * @return boolean
     */
    public static function increment($key,$offset=1){
        $mcached = self::connect();
        if ($mcached) {
            return $mcached->increment($key,$offset);
        }
        return false;
    }
    /**
     * 减少数值元素的值
     * @param string $key key值
     * @param intger $offset 要增加的值 
     * @return type
     */
    public static function decrement($key,$offset=1){
        $mcached = self::connect();
        if ($mcached) {
            return $mcached->decrement($key,$offset);
        }
        return false;
    }
    /**
     * 旧的有使用memcache close方法的
     */
    public function close(){
        
    }
}