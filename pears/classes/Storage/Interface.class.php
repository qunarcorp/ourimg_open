<?php

/**
 * Class Store_Interface
 * 存储相关接口
 */
interface Storage_Interface
{
    /**
     * 获取文件key
     * @param string $SourceFile
     * @param string $path
     * @param string $ext
     * @return mixed
     */
    public static function getKey(string $SourceFile,string $path,string $ext);

    /**
     * 上传
     * @param string $SourceFile
     * @param string $path
     * @param string $ext
     * @return mixed
     */
    public static function put(string $SourceFile,string $path,string $ext);

    /**
     * 复制
     * @param string $key
     * @param string $newkey
     * @return mixed
     */
    public static function copy(string $key,string $newkey);

    /**
     * 获取并另存
     * @param string $key
     * @param string $savefile
     * @return mixed
     */
    public static function get(string $key,string $savefile);

    /**
     * 删除
     * @param string $key
     * @param int $expire_time
     * @return mixed
     */
    public static function del(string $key,int $expire_time);

    /**
     * 获取完整上传完整路径
     * @param string $object_path
     * @param string $domain_type
     * @return mixed
     */
    public static function getObjectFullPath(string $object_path,string $domain_type='inner_domain');

    /**
     * 生成图片缩略图
     * @param string $img_path
     * @param int $r_width
     * @param int $r_height
     * @param string $domain_type
     * @param array $other
     * @return mixed
     */
    public static function generateImgThumbnail(string $img_path,int $r_width,int $r_height,string $domain_type='inner_domain',array $other=[]);

}