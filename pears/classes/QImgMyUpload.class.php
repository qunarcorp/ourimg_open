<?php

/**
 * 图片用户授权相关
 */

class QImgMyUpload {
    /*
     * 用户授权
     */
    public static function userAuth($params=[]){
        $username = $params['username'] ? $params['username'] : '';
        if( !$username ){
            return false;
        }
        
        $now_time = date("Y-m-d H:i:s");
        $update_arr = [
            'auth_state' => 1,
            'auth_time' => $now_time,
            'update_time' => $now_time,
        ];
        $update_rs = DB::Update(QImgSearch::$userTableName, $username, $update_arr, 'username');
        
        //发送邮件
        self::authMail(['auth_time'=>$now_time]);
        
        return $update_rs ? true : false;
    }
    
    /*
     * 用户授权邮件提醒
     */
    public static function authMail($params=[]){

        return true;
        /*
         * 使用前引入
         * require_once DIR_LIBARAY."/fpdf181/chinese.php";
         */
        global $login_user_name;
        global $login_real_name;
        global $INI;
        
        //邮件内容标题
        $date_str = date("Y-m-d");
        $pdf_file_path = $INI['stats']['authMail_root_path']."/".$date_str."/";
        if( !is_dir($pdf_file_path) ){
            mkdir($pdf_file_path, 0777, true);
        }
        
        $pdf_file_name = $pdf_file_path.$login_user_name.".pdf";
        
        $emails = $login_user_name."@test.com";//这里需 要改成自已的邮件后缀或其他规则
        $subject = "授权成功通知";
        $message = "亲爱的";
        
        //邮件附件

        $pdf = new PDFChinese('P','mm','A4');
        $pdf->AddGBFont ('GB',iconv("UTF-8","gbk",'PingFangSC')); 
        $pdf->AddPage();
        
        //文字相关配置--px和pt的转换  8px = 6pt
        $px_to_pt_rate = 0.75;
        
        //标红
        $mark_red_red = 97;
        $mark_red_green = 97;
        $mark_red_blue = 97;
        
        //灰色字体
        $mark_gray_red = 242;
        $mark_gray_green = 117;
        $mark_gray_blue = 161;
        
        //
        $pdf->SetFont('GB', '', $px_to_pt_rate * 22); 
        $pdf->SetTextColor(0, 0, 0, 0.85);
        $pdf->Cell(0,8,iconv("UTF-8","gbk",'著作权统一授权声明书'),0,0,'C');
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont ('GB', '', $px_to_pt_rate * 14);
        $pdf->SetTextColor($mark_red_red, $mark_red_green, $mark_red_blue);
        $pdf->Write (9, iconv("UTF-8","gbk",
                '          本人为'));
        $pdf->Ln();
        $pdf->Ln();
        $pdf->SetFont ('GB', 'B', $px_to_pt_rate * 16);
        $pdf->Cell(0,6,iconv("UTF-8","gbk",'声明人：               '),0,0,'R');
        $pdf->SetTextColor($mark_gray_red, $mark_gray_green, $mark_gray_blue);
        $pdf->Cell(0,6,iconv("UTF-8","gbk",$login_real_name),0,0,'R');
        $pdf->Ln();
        $pdf->Ln();
        $date = date("Y年m月d日",strtotime($params['auth_time']));
        $pdf->SetTextColor($mark_red_red, $mark_red_green, $mark_red_blue);

        $pdf->Cell(0,6,iconv("UTF-8","gbk",'声明日期：                                  '),0,0,'R');
        $pdf->SetTextColor($mark_gray_red, $mark_gray_green, $mark_gray_blue);
        $pdf->Cell(0,6,iconv("UTF-8","gbk",$date),0,0,'R');

        $pdf->Output($pdf_file_name,'F'); 
        
        $pdf_file_info = [
            'file' => $pdf_file_name,
            'name' => '授权声明书.pdf',
        ];
        QNotify_Mailer::mail($emails, $subject, $message, $pdf_file_info);
    }
    
}
