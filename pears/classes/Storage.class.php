<?php
/**
 * Class Store
 * 存储
 */

class Storage implements Storage_Interface
{
    private static $config = null;
    private static $storage = null;
    public static function &Instance()
    {
        global $INI;
        static::$config = $INI['storage'];
        if (!is_object(static::$storage)) {
            # driver class 组成
            $driver_class = "Storage_Driver_".ucwords(static::$config['storage_type']);
            static::$storage = new $driver_class (static::$config);
        }
    }

    public static function getKey(string $SourceFile, string $path, string $ext)
    {
        static::Instance();
        return static::$storage::getKey($SourceFile,$path,$ext);
    }

    public static function put(string $SourceFile, string $path, string $ext)
    {
        static::Instance();
        return static::$storage::put($SourceFile,$path,$ext);
    }

    public static function get(string $key, string $savefile)
    {
        static::Instance();
        return static::$storage::get($key,$savefile);
    }
    public static function copy(string $key, string $newkey)
    {
        static::Instance();
        return static::$storage::copy($key,$newkey);
    }

    public static function del(string $key, int $expire_time=10)
    {
        static::Instance();
        return static::$storage::del($key,$expire_time);
    }

    public static function getObjectFullPath(string $object_path, string $domain_type = 'inner_domain')
    {
        static::Instance();
        return static::$storage::getObjectFullPath($object_path,$domain_type);
    }

    public static function generateImgThumbnail(string $img_path, int $r_width, int $r_height, string $domain_type = 'inner_domain', array $other = [])
    {
        static::Instance();
        return static::$storage::generateImgThumbnail($img_path,$r_width,$r_height,$domain_type,$other);
    }

    /*
     * 验证图片md5是否已经存在
     * $params[]
     * img_upload_tmp
     * system_domain
     * img_ext
     */
    public static function md5KeyCheck($params = [])
    {
        static::Instance();
        $result = static::$storage::md5KeyCheck($params);

        $md5_key = $result['md5_key'];
        $md5_key_logo = $result['md5_key_logo'];


        $sql = " SELECT i.title AS title, i.file_name AS file_name, i.url AS url, i.width AS width , 
                i.height AS height, i.id AS img_id, i.eid AS img_eid, i.audit_state AS audit_state,
                i.username AS username, u.name AS name FROM "
            . QImgSearch::$imgTableName
            . " i JOIN " . QImgSearch::$userTableName
            . " u ON i.username = u.username  WHERE ( i.url = '{$md5_key}' OR i.logo_url = '{$md5_key_logo}' ) AND i.is_del = 'f' AND i.audit_state NOT IN (3, 4) ";
        $db_rs = DB::GetQueryResult($sql, false);
        return $db_rs ? $db_rs : false;
    }
}