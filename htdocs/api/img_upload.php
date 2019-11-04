<?php

/**
 * 上传文件
 * 度假搜索技术组 gps搜索
 */


require_once __DIR__."/../app_api.php";
session_write_close();//关闭session
require(DIR_ROOT."/conf/dictionary/upload.php");

$json = array("status"=>1,"message"=>"","data"=>[]);
$callback = filter_input(INPUT_GET,"callback",FILTER_SANITIZE_STRING);

//验证用户登录
QImgPersonal::checkUserLogin(['callback'=>$callback]);

$big_type = filter_input(INPUT_POST,"big_type",FILTER_SANITIZE_NUMBER_INT);
//没有要上传的图片
if(empty($_FILES["image"])){
    $json['status'] = 101;
    $json['message'] = "参数错误没有上传的文件";
    display_json_str_common($json,$callback);
}
//参数错误
if(!isset($dic_img['big_type'][$big_type])){
    $json['status'] = 101;
    $json['message'] = "参数错误";
    display_json_str_common($json,$callback);
}

if($_FILES['image']['error']){
    //有错误信息
    $json['status'] = 102;
    $json['message'] = $upload_config['upload_error'][$_FILES['image']['error']];
    display_json_str_common($json,$callback);
}

//图片的原标题
$file_name = $_FILES["image"]["name"];

$old_ext = strtolower(strrchr($file_name,"."));



$is_heic = $old_ext == '.heic';
if($is_heic){
    //如果是ios heic格式的的。 需要处理一下 转换jpg
    $img_ext = "jpg";
    //生成有文件名的临时文件 为什么要rename 临时文件
    $img_upload_old = $_FILES["image"]["tmp_name"].".heic";
    $img_upload_tmp = $_FILES["image"]["tmp_name"].".jpg";

    if(!file_exists("/usr/bin/convert") || !move_uploaded_file($_FILES["image"]["tmp_name"], $img_upload_old)){
        //上传失败
        $json['status'] = 106;
        $json['message'] = "上传失败";
        $json['message_ext'] = "拷贝临时文件出错";
        display_json_str_common($json,$callback);
    }
    //执行 convent 将heic 转换成 jpg
    $covert = shell_exec("/usr/bin/convert {$img_upload_old} $img_upload_tmp");

    if(!file_exists($img_upload_tmp)){
        @unlink($img_upload_old);
        //上传失败
        $json['status'] = 106;
        $json['message'] = "上传失败";
        $json['message_ext'] = "拷贝临时文件出错";
        display_json_str_common($json,$callback);
    }
    list($img_width, $img_height, $img_type, $img_attr) = getimagesize($img_upload_tmp);
}else{
    list($img_width, $img_height, $img_type, $img_attr) = getimagesize($_FILES["image"]["tmp_name"]);
}



if($_FILES["image"]["size"]>$upload_config['max_img_size'] ){
    //有错误信息
    $json['status'] = 104;
    $json['message'] = "文件太大无法上传";
    @unlink($_FILES["image"]["tmp_name"]);
    display_json_str_common($json,$callback);
}
if($img_type && !isset($upload_config['file_type'][$img_type])){
    $json['status'] = 105;
    $json['message'] = "只能上传规定图片";
    @unlink($_FILES["image"]["tmp_name"]);
    display_json_str_common($json,$callback);
}

//图片字节数
$file_size = $_FILES["image"]["size"];
//获取文件的扩展 png tif tiff jpg jpeg
$img_ext = image_type_to_extension($img_type,false);

if($img_ext=="jpeg"){
    $img_ext = "jpg";
}elseif($img_ext=="tiff"){
    $img_ext = "tif";
}


if(!$is_heic){
    //不是 heic 格式的 需要重新 move 一下
    //生成有文件名的临时文件 为什么要rename 临时文件
    $img_upload_tmp = $_FILES["image"]["tmp_name"].".{$img_ext}";

    if(!move_uploaded_file($_FILES["image"]["tmp_name"], $img_upload_tmp)){
        //上传失败
        $json['status'] = 106;
        $json['message'] = "上传失败";
        $json['message_ext'] = "考备临时文件出错";
        display_json_str_common($json,$callback);
    }
}

//扩展信息
$img_exif    = exif_read_data( $img_upload_tmp,"ANY_TAG",true );

/**
 * 不同角度拍摄的照片，宽高会反，在不同的浏览器里看到的图像可有不一样
 * 相机
 * http://blog.sina.com.cn/s/blog_651251e60102uz3d.html exif详解
 * http://sylvana.net/jpegcrop/exif_orientation.html
 * https://www.impulseadventure.com/photo/exif-orientation.html
 */

if("jpg" == $img_ext && in_array($img_exif['IFD0']['Orientation'],array(5,6,7,8))){
    $tmp_width = $img_width;
    $img_width=$img_height;
    $img_height=$tmp_width;
}
/*
 * 图片md5验证，如果已经存在直接返回结果，不再保存上传
 * 图片md5已经存在，不需要重复上传，只判断自己上传的图片
 */
$key_check_params = [
    'img_upload_tmp' => $img_upload_tmp,
    'system_domain' => $system_domain,
    'login_user_name' => $login_user_name,
    'img_ext' => $img_ext,
];
        
$key_check_rs = Storage::md5KeyCheck($key_check_params);
if( $key_check_rs ){
    //获取展示信息
    $repeat_rs = [];
    foreach( $key_check_rs as $k => $v ){
        $small_img120 = QImg::getImgUrlResize(array(
                    "img"=>$v['url'],
                    "width"=>$v['width'],
                    "height"=>$v['height'],
                    "r_width"=>120,
                    "r_height"=>120,
                    'system_domain'=>$system_domain,
                    "in"=>"inner_domain")
                    );//图片原图
        $repeat_rs[] = [
            'small_img120' => $small_img120,
            'file_name' => $v['file_name'],
            'title' => $v['title'],
            'audit_state' => $v['audit_state'],
            'img_eid' => $v['img_eid'],
            'userinfo' => [
                'is_login_user' => $v['username'] == $login_user_name ? true : false,
                'realname' => $v['name'],
            ],
        ];
    }
    
    $json['status'] = 108;
    $json['message'] = "图片已经存在，不能重复上传";
    $json['message_ext'] = "图片已经存在，不能重复上传";
    $json['data'] = $repeat_rs;
    display_json_str_common($json,$callback);
}

//修改图片路径
$upload_result = Storage::put($img_upload_tmp,$system_domain,$img_ext);
@unlink($img_upload_tmp);
if(empty($upload_result['key'])){
    //上传失败
    $json['status'] = 106;
    $json['message'] = "上传失败";
    $json['message_ext'] = $upload_result['message'];
    display_json_str_common($json,$callback);
}



//普通用户权限图片上传限制宽度在2000以上，超级管理员、管理员权限上传限制宽度在1000以上
if( in_array( 'admin' , $_SESSION['login_info']['role'] ) ){
    $upload_config['min_width'] = 999;
}

if($img_width <= $upload_config['min_width'] || $img_height <= $upload_config['min_height'] ){
    //有错误信息
    $json['status'] = 103;
    $json['message'] = "图片尺寸太小";

    $json['data'][]["small_img120"] = QImg::getImgUrlResize(array("img"=>$upload_result['key'],"width"=>$img_width,"height"=>$img_height,"r_width"=>120,"r_height"=>120,'system_domain'=>$system_domain,"in"=>"inner_domain"));//图片上传的缩略图地址或者是缩略图

    //文件太小的 上传上去5分钟后删除
    QImgDownload::smallImgByDownLoadDel($upload_result['key'],300);

    display_json_str_common($json,$callback);
}

$city = array();
$county = array();
$sight_info = array();
if(isset($img_exif['GPS']) && isset($img_exif['GPS']['GPSLongitude']) && isset($img_exif['GPS']['GPSLongitudeRef']) && isset($img_exif['GPS']['GPSLatitude']) && isset($img_exif['GPS']['GPSLatitudeRef'])){
    $gps_array = QImgGps::imgGpsToLngLat($img_exif['GPS']['GPSLongitude'],$img_exif['GPS']['GPSLongitudeRef'],$img_exif['GPS']['GPSLatitude'],$img_exif['GPS']['GPSLatitudeRef']);
    $GpsMap = new GpsMap();
    //wgs84是国标 转换成gcj02 google 使用的
    list($lng,$lat) = $GpsMap->wgs84togcj02($gps_array['lng'],$gps_array['lat']);

    //只获取到城市
    $city =  QSight::gps_to_city($lng,$lat,1);//seq name
    //获取区县
    $county =  QSight::gps_to_city($lng,$lat,0);

    if (!empty($city['seq'])) {
        $sight_info = QSight::sight_info($city['seq']);

        $sight_ltree = QSight::sight_city_json($sight_info);
    }
}

/**
 * 获取图片尺寸
 */
$size_type = 0;
foreach($dic_img['size_type_map'] as $key=>$val){
    if($img_width>=$val['width']){
        $size_type = $key;
        break;
    }
}
$insert = array();

$insert['domain_id'] = $system_domain;//所属网站
$insert['username'] = $login_user_name;//上传的用户
$insert['file_name'] = $file_name;//图片的原始名称
$insert['ext'] = $img_ext;//扩展名
$insert['url'] = $upload_result['key'];//上传上去的目录

if( $sight_ltree ){
    $insert['location'] = $sight_ltree ;//ltree 国家 格式 中国.河北省.保定市.涿州市
}

$insert['city_id'] = $city['seq'];//城市sight_id
$insert['place'] = isset($county['name'])?$county['name']:"";//城市区县 地点 poi
$insert['big_type'] = $big_type;//大分类
$insert['filesize'] = $file_size;//文件大小

$insert['width'] = $img_width;//宽
$insert['height'] = $img_height;//高

$insert['size_type'] = $size_type;//图片尺寸 大小

$insert['audit_state'] = 0;//待提交
$insert['user_ip'] = Utility::GetRemoteIp();//用户ip
$current_time = date("Y-m-d H:i:s");
$insert['create_time'] = $current_time;
$insert['update_time'] = $current_time;

DB::TransBegin();

$id = DB::Insert("public.img",$insert,"id");
if(!$id){
    DB::TransRollback();
    //上传失败
    $json['status'] = 107;
    $json['message'] = "保存数据失败";
    $json['message_ext'] = "img1";
    display_json_str_common($json,$callback);
}else{
    $IDEncipher = new IDEncipher();
    $eid = $IDEncipher->encrypt($id);
    $updata = array();
    $updata["eid"] = $eid;
    $rs = DB::Update("public.img",$id,$updata,"id");
    if(!$rs){
        DB::TransRollback();
        //上传失败
        $json['status'] = 107;
        $json['message'] = "保存数据失败";
        $json['message_ext'] = "img";
        display_json_str_common($json,$callback);
    }
}
//以下三个unset 是由于 里边存在[\u0000-\u001f]入库失败 BGN
unset($img_exif['EXIF']['ComponentsConfiguration']);
unset($img_exif['GPS']['GPSProcessingMode']);
unset($img_exif['GPS']['GPSAltitudeRef']);
//以下三个unset 是由于 里边存在[\u0000-\u001f]入库失败 END

//只存储相机的位置以及 原始gps信息
$img_exif_new = array();
if($img_exif['IFD0'] && $img_exif['IFD0']['Orientation']){
    $img_exif_new['IFD0']['Orientation'] = $img_exif['IFD0']['Orientation'];
}
if(isset($img_exif['GPS']) && isset($img_exif['GPS']['GPSLongitude']) && isset($img_exif['GPS']['GPSLongitudeRef']) && isset($img_exif['GPS']['GPSLatitude']) && isset($img_exif['GPS']['GPSLatitudeRef'])) {
    $img_exif_new['GPS']['GPSLongitude'] = $img_exif['GPS']['GPSLongitude'];
    $img_exif_new['GPS']['GPSLongitudeRef'] = $img_exif['GPS']['GPSLongitudeRef'];
    $img_exif_new['GPS']['GPSLatitude'] = $img_exif['GPS']['GPSLatitude'];
    $img_exif_new['GPS']['GPSLatitudeRef'] = $img_exif['GPS']['GPSLatitudeRef'];
}
$insert = array();;
$insert["img_id"] = $id;
$insert["extend"] = array();
$insert["extend"]["detail"] = [
    'filesize' => $file_size,//大小
    'filesize_convert' => byte_convert($file_size),//大小
    'img_ext' => $img_ext,//格式
    'img_width' => $img_exif['COMPUTED']['Width'],//尺寸
    'img_height' => $img_exif['COMPUTED']['Height'],//尺寸
    'Make' => $img_exif['IFD0']['Make'],//设备制造商
    'Model' => $img_exif['IFD0']['Model'],//设备型号
    'ColorSpace' => $img_exif['EXIF']['ColorSpace'],//颜色空间
    'FocalLength' => $img_exif['EXIF']['FocalLength'],//焦距
    'FNumber' => $img_exif['EXIF']['FNumber'],//光圈数
    'ExposureMode' => $img_exif['EXIF']['ExposureMode'],//测光模式
    'ExposureProgram' => $img_exif['EXIF']['ExposureProgram'],//曝光程序
    'ExposureTime' => $img_exif['EXIF']['ExposureTime'],//曝光时间
    //颜色描述文件
    //Alpha通道
    //红眼
];

$insert["extend"]["extif"] = $img_exif_new;
$insert["extend"]["sight_info"] = $sight_info;
$insert["extend"]["google_gps"] = array("lng"=>$lng,"lat"=>$lat);
//extif里边含有\u0000\u00001 pg不认识这些字符需要替换
$insert["extend"] = json_encode($insert["extend"],JSON_UNESCAPED_UNICODE);

$img_ext_id = DB::Insert("public.img_ext",$insert,"img_id");
if(!$img_ext_id){
    DB::TransRollback();
    //上传失败
    $json['status'] = 107;
    $json['message'] = "保存数据失败";
    $json['message_ext'] = "img_ext";
    display_json_str_common($json,$callback);
}

DB::TransCommit();
$json['status'] = 0;
$json['data']["eid"] = $eid;
$json['data']["img"] = QImg::getImgUrlResize(array("img"=>$upload_result['key'],"width"=>$img_width,"height"=>$img_height,"r_width"=>800,"r_height"=>0,'system_domain'=>$system_domain,"in"=>"inner_domain"));//图片上传的缩略图地址或者是缩略图
$json['data']["img_rezise"] = QImg::getImgUrlResize(array("img"=>$upload_result['key'],"width"=>$img_width,"height"=>$img_height,"r_width"=>500,"r_height"=>0,'system_domain'=>$system_domain,"in"=>"inner_domain"));//图片上传的缩略图地址或者是缩略图
$json['data']["file_name"] = basename($file_name,strrchr($file_name,"."));//原始的图片名称
$json['data']["size"] = byte_convert($file_size);
$json['data']["ext"] = $img_ext;
$json['data']["width"] = $img_width;
$json['data']["height"] = $img_height;
$json['data']["width_height"] = "{$img_width}x{$img_height}px";
$json['data']["city"] = $city['name'];
$json['data']["city_id"] = $city['seq'];
$json['data']["extend"] = QImgInfo::exifInfo(['extend' => $insert["extend"]]);
$json['data']["place"] = $county['name']&&$county['name']!=$city['name']?$county['name']:"";//图片地点
$json['message'] = "";
display_json_str_common($json,$callback);
