<?php

/**
 * 给原始图片添加水印图片
 * 每分钟执行
 */

ini_set("memory_limit","2048M");
require_once __DIR__."/../../htdocs/app_api.php";

//保证当前只有一个任务在执行
crontab_run_one("crontab","img_add_logo_mark");
//记录日志，开始执行
QLog::info("crontab","img_add_logo","开始修改图片：添加水印logo");

$dst_new = 'new_img.'.$img_ext;
//水印图像
$src = __DIR__."/logo_20190315.png";
$src_im_origin = @imagecreatefrompng($src);
list($logo_dst_w, $logo_dst_h) = getimagesize($src);
if( !$logo_dst_h ){
    QLog::info("crontab","img_add_logo","水印图片信息获取有误");
    exit;
}

//计算当前图片和水印图比例，确定哪个坐标可以固定
$logo_rate = $logo_dst_w/$logo_dst_h;//logo的宽高比

$reason = $dic_img['reject_reason'][8];
while (true) {
    //输出缩放后的图像
    $src_im = 'logo_new.png';
    
    //获取需要处理的图片信息
    $sql = " SELECT * FROM ". QImgSearch::$imgTableName
            ." WHERE logo_url IS NULL AND ( reject_reason IS NULL OR '{$reason}' != ALL(reject_reason) ) ORDER BY id ASC LIMIT 1 ";
    $img_info = DB::GetQueryResult($sql);
    
    
    
    //没有需要处理的图片，退出循环
    if( empty($img_info) || !is_array($img_info) ){
        QLog::info("crontab","img_add_logo","没有查询到需要添加水印的图片");
        break;
    }
    
    //获取图片原地址
    $dst_path = QImg::getImgUrl($img_info['url'],$system_domain,"inner_domain");
    if( !$dst_path ){
        QLog::info("crontab","img_add_logo","获取图片原图地址失败");
        break;
    }
    
    //对比查看原图的1000图片进行缩放--logo图
    if( $img_info['width'] > $img_info['height'] ){
        $largest_side_length = $img_info['width'];
    }else{
        $largest_side_length = $img_info['height'];
    }

    $scaling_ratio = $largest_side_length/1000;
    $logo_new_width = $scaling_ratio * $logo_dst_w;
    $logo_new_height = $scaling_ratio * $logo_dst_h;

    //初始化图像
    $src_im_tmp = imagecreate($logo_new_width, $logo_new_height);

    // 调整大小
    imagecopyresized($src_im_tmp, $src_im_origin, 0, 0, 0, 0, $logo_new_width, $logo_new_height, $logo_dst_w, $logo_dst_h);

    imagepng($src_im_tmp, $src_im);
    $src_im = @imagecreatefrompng($src_im);

    //图片添加水印，本地图片和远程图片都可以
    list($dst_w, $dst_h, $dst_type) = getimagesize($dst_path);
    if( !$dst_type ){
        QLog::info("crontab","img_add_logo","图片格式信息有误");
        break;
    }
    
    switch ($dst_type) {
        case 1://GIF
            header('Content-Type: image/gif');
            $dst_im = imagecreatefromgif($dst_path);
            $output_fun = 'imagegif';
            break;
        case 2://JPG
            header('Content-Type: image/jpeg');
            $dst_im = @imagecreatefromjpeg($dst_path);
            $output_fun = 'imagejpeg';
            break;
        case 3://PNG
            header('Content-Type: image/png');
            $dst_im = imagecreatefrompng($dst_path);
            $output_fun = 'imagepng';
            break;
        default:
            QLog::info("crontab","img_add_logo","图片格式信息有误");
            break;
    }
    
    //图片有误，生成失败，直接驳回
    if( !$dst_im ){
        $update_arr = [
            'img_id' => $img_info['id'],
            'img_info' => $img_info,
        ];
        
        QAudit_SystemCheck::reject($update_arr);
        QLog::info("crontab","img_add_logo","图片信息读取有误，logo生成失败，图片已驳回");
        break;        
    }
    
    //获取文件的扩展 png tif tiff jpg jpeg
    $img_ext = image_type_to_extension($dst_type,false);
    
    if($img_ext=="jpeg"){
        $img_ext = "jpg";
    }elseif($img_ext=="tiff"){
        $img_ext = "tif";
    }
    
    //由于有宽高倒置的图片
    $exif_data = exif_read_data($dst_path);
    if( !empty($exif_data['Orientation']) ){
        switch ($exif_data['Orientation']){
            case 8:
                $dst_im = imagerotate($dst_im, 90, 1);
                $dst_tmp = $dst_w;
                $dst_w = $dst_h;
                $dst_h = $dst_tmp;
                break;
            case 3:
                $dst_im = imagerotate($dst_im, 180, 1);
                break;
            case 6:
                $dst_im = imagerotate($dst_im, -90, 1);
                $dst_tmp = $dst_w;
                $dst_w = $dst_h;
                $dst_h = $dst_tmp;
                break;
        }
    }
    
    //计算当前图片和水印图比例，确定哪个坐标可以固定
    $img_rate = $dst_w/$dst_h;//当前图片的宽高比
    if( $img_rate >= $logo_rate ){//x坐标固定
        $end_x = ($dst_w-$logo_new_width)/2;
        $begin_x = ($dst_w+$logo_new_width)/2;

        $end_y = $end_x/$dst_w * $dst_h;
        $begin_y = $dst_h/2 + ($dst_h/2 - $end_y);

    }else{//y坐标固定
        $end_y = ($dst_h-$logo_new_height)/2;
        $begin_y= ($dst_h+$logo_new_height)/2;

        $end_x = $end_y/$dst_h * $dst_w;
        $begin_x = $dst_w/2 + ($dst_w/2 - $end_x);
    }

    //设置对角线线条颜色
    $write_color = imagecolorallocate($dst_im, 0xff, 0xff, 0xff);
    //判断水印执行结果
    if( !$write_color ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：生成线条白颜色失败");
        continue;
    }
    //左上白线
    $line_rs1 = imageline($dst_im,0,0,$end_x - 5,$end_y - 5,$write_color);
    //判断水印执行结果
    if( !$line_rs1 ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：左上线条画线失败");
        continue;
    }
    
    //右下白线
    $line_rs2 = imageline($dst_im,$begin_x,$begin_y,$dst_w,$dst_h,$write_color);
    //判断水印执行结果
    if( !$line_rs2 ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：右下线条画线失败");
        continue;
    }
    
    //左下白线
    $line_rs3 = imageline($dst_im,0,$dst_h, $end_x + 5,$begin_y + 5,$write_color);
    //判断水印执行结果
    if( !$line_rs3 ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：左下线条画线失败");
        continue;
    }
    
    //右上白线
    $line_rs4 = imageline($dst_im,$begin_x,$end_y,$dst_w,0,$write_color);
    //判断水印执行结果
    if( !$line_rs4 ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：右上线条画线失败");
        continue;
    }

    //合并水印图片：浮水印的图若是透明背景、透明底图
    //设置刷水印的画线图像
    $logo_rs1 = imagesetbrush($dst_im, $src_im);
    //判断水印执行结果
    if( !$logo_rs1 ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：设置水印画线图像失败");
        continue;
    }
    
    //将水印图画上去
    $logo_rs2 = imageline($dst_im, $dst_w/2, $dst_h/2, $dst_w/2, $dst_h/2, IMG_COLOR_BRUSHED);
    //判断水印执行结果
    if( !$logo_rs2 ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：水印图画线失败");
        continue;
    }

    //修改图片路径
    $img_upload_tmp = $dst_new."{$img_ext}";
    //输出合并后水印图片
    $output_rs = $output_fun($dst_im, $img_upload_tmp);
    //判断水印执行结果
    if( !$output_rs ){
        //销毁临时图片
        imagedestroy($dst_im);
        imagedestroy($src_im_tmp);
        imagedestroy($src_im);//销毁水印图
        QLog::info("crontab","img_add_logo","添加水印失败：输出加水印后图像失败");
        continue;
    }
    
    //图片添加水印后销毁生成的临时图片
    imagedestroy($dst_im);
    imagedestroy($src_im_tmp);
    imagedestroy($src_im);//销毁水印图
    $upload_result = Storage::put($img_upload_tmp,$system_domain."/addlogo",$img_ext);
    if( !$upload_result['key'] ){//上传失败
        QLog::info("crontab","img_add_logo","上传加水印图片失败，失败信息：". var_export($upload_result, true));
        continue;
    }
    
    //上传成功，更新到数据库
    $update_arr = [
        'logo_url' => $upload_result['key'],
    ];
    $db_rs = DB::Update(QImgSearch::$imgTableName, $img_info['id'], $update_arr, 'id');
    QLog::info("crontab","img_add_logo","图片添加水印成功，img表id：{$img_info['id']}；上传结果：". var_export($upload_result, true));
}

QLog::info("crontab","img_add_logo","结束修改图片：添加水印logo");


