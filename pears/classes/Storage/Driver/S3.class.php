<?php

/**
 *  aws 类
 * 基本配置
 * https://docs.aws.amazon.com/zh_cn/sdk-for-php/v3/developer-guide/getting-started_basic-usage.html
 *
 * api
 * https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.S3.S3Client.html
 */

require_once DIR_ROOT . "/vendor/autoload.php";

class Storage_Driver_S3 implements Storage_Interface
{

    private static $config = array();//配置 key secret endpoing Bucket 来源于configure/storage.php
    private static $s3 = null;//s3 声明的对象


    /**
     * $INI['storage']
     * Store_Driver_S3 constructor.
     * @param $config
     */
    public function __construct($config)
    {
        static::$config = $config;
    }


    public static function &Instance()
    {
        if (!is_object(static::$s3)) {
            $api_begin_time = get_now_microtime();
            $credentials = new Aws\Credentials\Credentials(static::$config ['key'], static::$config ['secret']);
            $s3 = new Aws\S3\S3Client([
                'version' => 'latest',
                'region' => static::$config ['region'],
                'endpoint' => static::$config ['endpoint'],
                'credentials' => $credentials,
                //'debug' => true,
                'signature_version' => static::$config ['signature_version'],
            ]);
            static::log("new_s3", get_now_microtime() - $api_begin_time, [], "");
            static::$s3 = $s3;
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
        $log_action = "storage_s3_" . $action;

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
            $md5_file = "{$path}/" . md5_file($SourceFile);
        }
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
     * $d = Storage::put(__DIR__."/gps.jpg","a","jpg");
     */
    public static function put(string $SourceFile, string $path, string $ext)
    {
        static::Instance();
        $api_begin_time = get_now_microtime();
        $return = array("key" => "", "message" => "", "url" => "");
        try {
            $key = static:: getKey($SourceFile, $path, $ext);
            $result = (static::$s3)->putObject([
                'Bucket' => static::$config ['Bucket'],
                'Key' => $key,
                'SourceFile' => $SourceFile,
            ]);
            //$result['ObjectURL'];
            if ($result['ObjectURL']) {
                $return['url'] = $result['ObjectURL'];
                $return['key'] = $key;
            }
            static::log("put", get_now_microtime() - $api_begin_time, $return, "");
        } catch (Exception $e) {
            $return['message'] = $e->getMessage();
            static::log("put", get_now_microtime() - $api_begin_time, [], $e->getMessage());
        }

        return $return;
    }

    /**
     * 复制对象
     * @param $SourceFile   存储的key 不带bucket
     * @param $path
     * @param $ext
     */
    public static function copy(string $key, string $newkey)
    {
        static::Instance();
        $api_begin_time = get_now_microtime();
        try {
            $result = (static::$s3)->copyObject([
                'Bucket' => static::$config ['Bucket'],
                'CopySource' => urlencode(self::getObjectBasePath($key)),
                'Key' => $newkey,
            ]);

            static::log("copy", get_now_microtime() - $api_begin_time, [], "ok");
            return $result;
        } catch (Exception $e) {
            static::log("copy", get_now_microtime() - $api_begin_time, [], $e->getMessage());
        }

        return false;
    }

    /**
     * 获取对象并存储到本地
     * @param $key
     * @param $savefile
     * @return bool
     */
    public static function get(string $key, string $savefile)
    {
        static::Instance();
        $api_begin_time = get_now_microtime();
        try {
            $result = (static::$s3)->getObject([
                'Bucket' => static::$config ['Bucket'],
                'Key' => $key,
                'SaveAs' => $savefile,
            ]);
            static::log("get", get_now_microtime() - $api_begin_time, [], "ok");
            return $result;
        } catch (Exception $e) {
            static::log("get", get_now_microtime() - $api_begin_time, [], $e->getMessage());
            $return['message'] = $e->getMessage();
        }
        return false;
    }

    /**
     * 删除对象
     * @param $key
     * @return bool
     * 此方法ops不允许使用
     * 使用其他方法代替， 可以通过同名替换和设置过期时间来达到相同的效果
     * https://docs.openstack.org/ocata/user-guide/cli-swift-set-object-expiration.html
     */
    public static function del(string $key, int $expire_time = 10)
    {
        static::Instance();

        $api_begin_time = get_now_microtime();
        try {
            $result = (static::$s3)->deleteObject([
                'Bucket' => static::$config ['Bucket'],
                'Key' => $key,
            ]);
            static::log("del", get_now_microtime() - $api_begin_time, [], "删除:{$key}" . var_export($result, true));
            return $result;
        } catch (Exception  $e) {

            static::log("del", get_now_microtime() - $api_begin_time, [], "删除:{$key};异常" . $e->getMessage());
            if (static::$config ['swift']['user'] && static::$config ['swift']['key']) {
                //如果不能删除 且配置了swift，就使用swift
                return static::delBySwift($key, $expire_time);
            }
        }
        return false;
    }

    /**
     * 使用swift 删除文件。 主要用于 有的存储不支持del
     * @param string $key
     * @param int $expire_time
     * @return bool
     * https://docs.openstack.org/ocata/user-guide/cli-swift-set-object-expiration.html
     */
    private static function delBySwift(string $key, int $expire_time = 10)
    {
        static::Instance();
        $api_begin_time = get_now_microtime();

        $shell_cmd = "export ST_AUTH=" . static::$config ['swift']['auth'] . ";export ST_USER=" . static::$config ['swift']['user'] . ";export ST_KEY=" . static::$config ['swift']['key'] . ";swift post " . static::$config ['Bucket'] . " " . $key . " -H 'X-Delete-After:" . $expire_time . "' 2>&1";

        $del_error = exec($shell_cmd, $output, $return_var);

        static::log("swift_del", get_now_microtime() - $api_begin_time, [], "命令:" . $shell_cmd . "return_ver:" . $return_var . ";返回:" . json_encode($del_error, JSON_UNESCAPED_UNICODE));

        return ($return_var === 0 || preg_match("/404 Not Found/", $del_error)) ? true : false;

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
    public static function getObjectFullPath(string $object_path, string $domain_type = 'inner_domain')
    {
        static::Instance();
        return static::$config['domain'][$domain_type] . "/" . static::$config['Bucket'] . "/" . $object_path;
    }
    /**
     * 获取最斟本的 文 件路径不含 domain
     * @param string $object_path
     * @return string
     */
    public static function getObjectBasePath(string $object_path){
        $object_path = str_replace("../","/",$object_path);
        return  "/" .static::$config['Bucket'] . "/" . $object_path;
    }
    /**
     *  生成图片的缩略图
     * @param $img_path
     * @param $r_width
     * @param $r_height
     * @param string $domain_type
     */
    public static function generateImgThumbnail(string $img_path,int $r_width, int$r_height, string $domain_type = 'inner_domain', array $other = [])
    {
        static::Instance();
        $method_name = "generateImgThumbnailBy" . ucfirst(static::$config['source']);

        if (!method_exists(__CLASS__, $method_name)) {
            return static::getObjectFullPath($img_path, $domain_type);
        } else {
            return static::$method_name ($img_path, $r_width, $r_height, $domain_type = 'inner_domain', $other = []);
        }
    }

    /**
     * 百度生成缩略图
     *
     * https://cloud.baidu.com/doc/BOS/s/zjwvysfe7/#%E5%9B%BE%E7%89%87%E5%A4%84%E7%90%86%E5%91%BD%E4%BB%A4
     * @param $img_path
     * @param $r_width
     * @param $r_height
     * @param string $domain_type
     * 百度仅支持自定义的图片处理类型，
     * https://cloud.baidu.com/doc/BOS/s/ijwvyr8ws/
     */
    private static function generateImgThumbnailByBaidu($img_path, $r_width, $r_height, $domain_type = 'inner_domain', $other = [])
    {
        static::Instance();
        $ext = "";
        if ($r_width > 0) {
            if (!empty($ext)) {
                $ext .= ",";
            }
            $ext .= "w_" . $r_width;
        }
        if ($r_height > 0) {
            if (!empty($ext)) {
                $ext .= ",";
            }
            $ext .= "h_" . $r_height;
        }
        if ($ext) {
            $ext = "@" . $ext;
        }
        return static::getObjectFullPath($img_path, $domain_type) . $ext;
    }

}