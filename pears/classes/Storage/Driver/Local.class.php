<?php

/**
 *
 */


class Storage_Driver_Local implements Storage_Interface
{

    private static $config = array();//配置 key secret endpoing Bucket 来源于configure/storage.php

    /**
     * @param $config
     */
    public function __construct($config)
    {
        static::$config = $config;
        if(empty(static::$config['storage_local_path'])){
            throw new Exception("storage_local_path 未配置" );
        }
    }

    /**
     * 记录日志
     */
    /**
     * @param $action 运行 put delete
     * @param $return array
     * @param $request_time 执行的时间 a-b
     * @param string $msg 错误信息 默认空
     */
    public static function log($action, $request_time, $return, $msg = "")
    {
        //外部系统接口均使用该值
        $log_prefix = "outapi";
        $log_action = "storage_local_" . $action;

        $log_info = array();
        $log_info['request_time'] = (sprintf("%.3f", $request_time)) * 1000; //ms
        $log_info['msg'] = $msg;
        $log_info['return'] = $return;

        QLog::write($log_prefix, $log_action, json_encode($log_info, JSON_UNESCAPED_UNICODE), 'info');
    }

    /**
     * 生成文件上传的key
     * @param string $SourceFile 真实存在的文件, 如果path = download 则是下载的链接
     * @param string $path 对应的目录
     * @param string $ext
     * @return string
     */
    public static function getKey(string $SourceFile, string $path, string $ext)
    {
        if ("download" == $path) {
            //下载的，不会重命名，因为下载的是按下载id算的。
            $md5_file = "{$path}/" . basename($SourceFile);
        } else {
            $md5_file = "{$path}/" ;
            $md5 = md5_file($SourceFile);

            //本地存储目录 以md6的8位数存储
            $md5_file .= implode("/",str_split($md5,8));
            $md5_file .= "/".$md5;
        }

        $real_path = static::getObjectRealPath($md5_file);
        static::mkdir($real_path);

        $key = $md5_file;
        if ($ext) {
            $key .= ".{$ext}";
        }
        return $key;
    }

    /**
     * @param $SourceFile /tmp/1.jpg
     * @param $ext  png|jpg|gif
     * return key url
     */
    public static function put(string $SourceFile, string $path, string $ext)
    {
        $api_begin_time = get_now_microtime();
        $return = array("key" => "", "message" => "", "url" => "");

        $key = static:: getKey($SourceFile, $path, $ext);

        $real_object_path = static::getObjectRealPath($key);

        if(!rename($SourceFile,$real_object_path)){
            $return['message'] = "{$SourceFile} rename {$key}，失败";
        }else{
            $return['url'] = static::getObjectFullPath($key);
            $return['key'] = $key;
        }
        static::log("put", get_now_microtime() - $api_begin_time, $return, "");

        return $return;
    }

    private static function mkdir($objectFullPath){
        $objectFullPath = trim(str_replace(["../","..\\"],"/",$objectFullPath));

        $dir = pathinfo($objectFullPath,PATHINFO_DIRNAME);
        if(!file_exists($dir)){
            mkdir($dir,0777,true);
        }

    }

    /**
     * 复制对象
     * @param $key   存储的key 不带bucket
     * @param $newkey
     * $result = $client->copyObject(array(
     * 'Bucket' => $bucket,
     * 'CopySource' => urlencode($bucket . '/'.'201711/404.html'),
     * 'Key' => '201711/606.html'
     * ));
     */
    public static function copy(string $key, string $newkey)
    {
        $api_begin_time = get_now_microtime();

        $real_object_path = static::getObjectRealPath($key);
        $new_real_object_path = static::getObjectRealPath($newkey);

        $return = new stdClass();
        $return->message = "";
        $return->ObjectURL = "";

        if(!file_exists($real_object_path)){
            $return->message = "{$key}另存为{$newkey} 源文件不存在";
            static::log("copy", get_now_microtime() - $api_begin_time, [], $return->message );
            return $return;
        }

        if(file_exists($new_real_object_path)){
            $return->message = "{$key}复制到{$newkey} 目标文件已存在,不能覆盖";
            static::log("copy", get_now_microtime() - $api_begin_time, [], $return->message);
            return $return;
        }

        static::mkdir($new_real_object_path);

        if(!copy($real_object_path,$new_real_object_path)){
            $return->message = "{$key}复制到{$newkey} ,复制失败1";
            static::log("copy", get_now_microtime() - $api_begin_time, [], $return->message);
            return $return;
        }

        if(!file_exists($new_real_object_path)){
            $return->message = "{$key}复制到{$newkey} ,复制失败2";
            static::log("copy", get_now_microtime() - $api_begin_time, [], $return->message);
            return $return;
        }
        static::log("copy", get_now_microtime() - $api_begin_time, [], "{$key}复制到{$newkey} ,复制成功");
        $return->ObjectURL = static::getObjectFullPath($newkey);
        return $return;
    }

    /**
     * 获取对象并存储到本地
     * 本地的源文件不允许操作， 只有下载，或者获取文件属性的时候才会用到get
     * @param $key
     * @param $savefile 要写具体的存储位置 例 /tmp/xxxx.jpg
     * @return bool
     */
    public static function get(string $key, string $savefile)
    {
        $api_begin_time = get_now_microtime();
        if(file_exists($savefile)){
            static::log("get", get_now_microtime() - $api_begin_time, [], "{$key}另存为{$savefile} 目标文件已存在,不能覆盖");
            throw new Exception("{$key}另存为{$savefile} 目标文件已存在,不能覆盖");
        }

        $real_object_path = static::getObjectRealPath($key);

        if(!copy($real_object_path,$savefile)){
            static::log("get", get_now_microtime() - $api_begin_time, [], "{$key}另存为{$savefile} ,复制失败1");
            return false;
        }

        if(!file_exists($savefile)){
            static::log("get", get_now_microtime() - $api_begin_time, [], "{$key}另存为{$savefile} ,复制失败1");
            return false;
        }
        static::log("get", get_now_microtime() - $api_begin_time, [], "{$key}另存为{$savefile} ,复制成功");
        return true;
    }

    /**
     * 删除对象
     * @param $key
     * @return bool
     */
    public static function del(string $key, int $expire_time = 10)
    {
        $real_object_path = static::getObjectRealPath($key);
        //判断文 件是否存在
        if(!file_exists($real_object_path)){
            return false;
        }
        return unlink($real_object_path);
    }


    /*
     * 验证图片md5是否已经存在
     */
    public static function md5KeyCheck($params = [])
    {
        $img_upload_tmp = $params['img_upload_tmp'] ? $params['img_upload_tmp'] : '';
        $system_domain = strlen($params['system_domain']) ? $params['system_domain'] : 0;
        $img_ext = $params['img_ext'] ? $params['img_ext'] : '';
        $md5_key = static::getKey($img_upload_tmp, $system_domain, $img_ext);
        $md5_key_logo = static::getKey($img_upload_tmp, $system_domain . '/addlogo', $img_ext);


        return ['md5_key'=>$md5_key,'md5_key_logo'=>$md5_key_logo];
    }

    /**
     *  获取对象的访问链接
     * @param $object_path
     * @param string $domain_type
     * @return string
     */
    public static function getObjectFullPath(string $object_path,string $domain_type = 'inner_domain')
    {
        return static::$config['domain'][$domain_type] . static::getObjectBasePath($object_path);
    }

    /**
     * 获取最斟本的 文 件路径不含 domain
     * @param string $object_path
     * @return string
     */
    public static function getObjectBasePath(string $object_path){
        $object_path = str_replace(["../","..\\"],"/",$object_path);
        return  "/" .static::$config['Bucket'] . "/" . $object_path;
    }

    /**
     * 获取对像真实路径
     * @param string $object_path
     * @return string
     */
    public static function getObjectRealPath(string $object_path){
        return static::$config['storage_local_path'].static::getObjectBasePath($object_path);
    }
    /**
     *  生成图片的缩略图
     * @param $img_path
     * @param $r_width
     * @param $r_height
     * @param string $domain_type
     */
    public static function generateImgThumbnail(string$img_path,int $r_width, int $r_height, string $domain_type = 'inner_domain',array $other = [])
    {
        $method_name = "generateImgThumbnailBy" . ucfirst(static::$config['source']);

        if (!method_exists(__CLASS__, $method_name)) {
            return static::getObjectFullPath($img_path, $domain_type);
        } else {
            return static::$method_name ($img_path, $r_width, $r_height, $domain_type = 'inner_domain', $other = []);
        }
    }

    /**
     *
     *  生成缩略图逻辑，在此处处理增加
     */
    private static function generateImgThumbnailByLocal($img_path, $r_width, $r_height, $domain_type = 'inner_domain', $other = [])
    {
        return static::getObjectFullPath($img_path, $domain_type);
    }
}