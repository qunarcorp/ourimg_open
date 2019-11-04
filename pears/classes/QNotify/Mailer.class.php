<?php

/**
 * 发送邮件
 * Class QNotify_Mailer
 */

require_once DIR_ROOT ."/vendor/autoload.php";
class QNotify_Mailer {

    /**
     * @param array $emails
     * @param $subject
     * @param $message
     * @param array $attach_file   单文件['file'=>"",'name'=>''], 多文件 [['file'=>"",'name'=>''],['file'=>"",'name'=>'']]
     * @param null $options
     * @param array $bcc
     * @return mixed
     *
     * QNotify_Mailer::mail(['aaaa@bb.com'],"test","testmessage",['file'=>__FILE__,'name'=>'测试文件']);
     */
    static public function mail($emails=array(), $subject, $message,$attach_file=[],$options=null,$bcc=[]){

        global $INI;
        settype($emails, 'array');
        $ishtml = ($options['html'] === true);
        //begin
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        $mail->CharSet = $INI['mail']['encoding'];

        switch ($INI['mail']['mail']){
            case 'mail':
                $mail->isMail();
                break;
            case 'smtp';
                $mail->isSMTP();

                $mail->SMTPAuth = $INI['mail']['smtp_auth'];
                $mail->Host = $INI['mail']['host'];
                $mail->Port = $INI['mail']['port'];
                $mail->SMTPSecure = $INI['mail']['ssl'];
                $mail->Username = $INI['mail']['user'];
                $mail->Password = $INI['mail']['pass'];
                # $mail->SMTPDebug = 3;
                break;
            case 'sendmail':
                $mail->isSendmail();
                break;
            case 'qmail':
                $mail->isQmail();
                break;
            default:
                $mail->isMail();
        }

        $mail->SetFrom($INI['mail']['from'], $INI['mail']['from_name']);
        $mail->AddReplyTo($INI['mail']['from'], $INI['mail']['from_name']);


        foreach($bcc AS $bo) {
            $mail->AddBCC($bo);
        }
        $mail->Subject = $subject;
        if ( $ishtml ) {
            $mail->MsgHTML($message);
        } else {
            $mail->Body = $message;
        }
        foreach($emails as $to){
            $mail->AddAddress($to);
        }

        if($attach_file){
            if(isset($attach_file['file'])){
                $attach_file = [$attach_file];
            }
            foreach($attach_file as $file){
                $mail->addAttachment($file['file'],$file['name']?:basename($file['file']));
            }
        }
        $is_send = $mail->send();
        return $is_send;
    }
}