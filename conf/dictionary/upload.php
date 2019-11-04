<?php
/**
 * 图片上传配置
 */

$dic_upload = array();

//所属网站 对应的上传限制，key 0 代表网站id ，后续可写到配置文件中，与域名管理保持一致

$dic_upload[0] = array(
    //去哪儿内部上传相关配置 0表示domain_id
    "min_width"=>1999,//图片最小宽度 对应img.php 图片最小尺寸
    "min_height"=>1,//最小高度
    "max_img_size"=>1073741824,//图片最大字节数 最大1G
    ////允许上传的文件类型
    "file_type"=>array("1" => "gif", "2" => "jpg", "3" => "png",  "6" => "bmp", "7"=>"tiff","8"=>"tiff","15" => "wbmp"),
    //$_FILES['userfile']['error']
    "upload_error"=>array(
        UPLOAD_ERR_OK=>"没有错误发生，文件上传成功。 ",//0文件上传成功
        UPLOAD_ERR_INI_SIZE=>"上传的文件超过了配置中选项限制的值",//1
        UPLOAD_ERR_FORM_SIZE=>"上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值。",//2
        UPLOAD_ERR_PARTIAL=>"文件只有部分被上传。 ",//3
        UPLOAD_ERR_NO_FILE=>"没有文件被上传",
        UPLOAD_ERR_NO_TMP_DIR=>"找不到临时文件夹",
        UPLOAD_ERR_CANT_WRITE=>"文件写入失败",
    )
);

$upload_config = $dic_upload[$system_domain];