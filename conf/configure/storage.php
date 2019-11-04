<?php
/**
 * 存储相关配置
 *
 * 百度的图片裁剪
 * https://cloud.baidu.com/doc/BOS/s/ijwvyr8ws/
 */

$value = array();

/*
//本地存储测试
//本地存储 需 要组合 {domain}/Bucket/{object}
///如果需要缩略图， 请修改 pears/Storage/Driver/Local.class.php/ generateImgThumbnail 以及对应的source generateImgThumbnailByLocal
//生成链接如 http://xx.com/upload/0/ee2d29d5/0765bf47/e598a451/31606154/ee2d29d50765bf47e598a45131606154.jpg
$value = [
    "storage_type" => "local",//支持s3 及本地存储 s3|local
    "source"=>'local', //生成缩略图以及相关 判断 使用
    "Bucket" => "upload",//上传的容器名
    "domain" => [
        //上传的内网域名， 注意域名不等于endpoint 此处的域名不能是bucket的
        "inner_domain" => "http://xx.com",
        //上传的外网域名
        "out_domain" => "http://xx.com",
    ],
    //文件存储位置 网站根目录/{bucket} 也可以是软链
    "storage_local_path"=> WWW_ROOT
];
*/

//百度测试
$value = [
    "storage_type"=>"s3",//支持s3 及本地存储
    "source"=>'baidu',//如果是s3 支持 来源 存储的源，用于判断不同的源有不同的resize 图片规则 @w_500,h_200
    "key"=>"",//S3 access_key
    "secret"=>"",//S3 secret_key
    "endpoint"=>"http://s3.bj.bcebos.com",//
    "Bucket"=>"sztest",//上传的容器名
    "region"=>"bj",// bj
    "signature_version"=>"v4",//签名版本v3,v4

    "domain"=>[
        //上传的内网域名， 注意域名不等于endpoint 此处的域名不能是bucket的
        "inner_domain"=>"https://bj.bcebos.com",
        //上传的外网域名
        "out_domain"=>"https://bj.bcebos.com",
    ]
];

