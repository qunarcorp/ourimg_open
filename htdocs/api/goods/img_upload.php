<?php

/**
 * 商城产品图片上传
 */

require(dirname(dirname(dirname(__FILE__))) . '/app_api.php');
require(DIR_ROOT."/conf/dictionary/upload.php");


$json = array("status"=>1,"message"=>"","data"=>[]);
//验证用户登录
must_login();

//没有要上传的图片
if(empty($_FILES["file"])){
    $json['status'] = 101;
    $json['message'] = "参数错误没有上传的文件";
    display_json_str_common($json,$callback);
}

if($_FILES['image']['error']){
    //有错误信息
    $json['status'] = 102;
    $json['message'] = $upload_config['upload_error'][$_FILES['image']['error']];
    display_json_str_common($json,$callback);
}


list($img_width, $img_height, $img_type, $img_attr) = getimagesize($_FILES["file"]["tmp_name"]);

if($_FILES["file"]["size"]>$upload_config['max_img_size'] ){
    //有错误信息
    $json['status'] = 104;
    $json['message'] = "文件太大无法上传";
    @unlink($_FILES["file"]["tmp_name"]);
    display_json_str_common($json,$callback);
}
if($img_type && !isset($upload_config['file_type'][$img_type])){
    $json['status'] = 105;
    $json['message'] = "只能上传规定图片";
    @unlink($_FILES["file"]["tmp_name"]);
    display_json_str_common($json,$callback);
}

//图片的原标题
$file_name = DB::EscapeString($_FILES["file"]["name"]);
//图片字节数
$file_size = $_FILES["file"]["size"];
//获取文件的扩展 png tif tiff jpg jpeg
$img_ext = image_type_to_extension($img_type,false);

if($img_ext=="jpeg"){
    $img_ext = "jpg";
}elseif($img_ext=="tiff"){
    $img_ext = "tif";
}



//生成有文件名的临时文件 为什么要rename 临时文件
$img_upload_tmp = $_FILES["file"]["tmp_name"].".{$img_ext}";

if(!move_uploaded_file($_FILES["file"]["tmp_name"], $img_upload_tmp)){
    //上传失败
    $json['status'] = 106;
    $json['message'] = "上传失败";
    $json['message_ext'] = "考备临时文件出错";
    display_json_str_common($json,$callback);
}

//修改图片路径
$upload_result = Storage::put($img_upload_tmp, $system_domain, $img_ext);
@unlink($img_upload_tmp);

if(empty($upload_result['key'])){
    //上传失败
    $json['status'] = 106;
    $json['message'] = "上传失败";
    $json['message_ext'] = $upload_result['message'];
    display_json_str_common($json,$callback);
}


$sql = <<<SQL
 insert into public.goods_img_record (img_key, file_name, width, height, img_size, username)
 values ('{$upload_result['key']}', '{$file_name}', '{$img_width}', {$img_height}, '{$file_size}', '{$login_user_name}')
 on CONFLICT DO NOTHING 
SQL;

DB::Query($sql);
success_return("上传成功", [
    "img_url" => QImg::getImgUrl($upload_result['key'], $system_domain, "inner_domain"),
    "img_resize" => QImg::getImgUrlResize(array("img"=>$upload_result['key'],"width"=>$img_width,"height"=>$img_height,"r_width"=>500,"r_height"=>0,'system_domain'=>$system_domain,"in"=>"inner_domain")),
    "size" => byte_convert($file_size),
]);