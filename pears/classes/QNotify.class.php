<?php

/**
 * 发送邮件，qt消息 统一处理
 * Class QNotify
 */

class QNotify
{
    /**
     * 合并 发送邮件，以及qtalk消息。 自主选择
     * @param array $data
     *              to array 发送的数组
     *              subject  string 发送的标题
     *              body     string 发送的内容
     *              attach_file array 附件 [['file'=>"",'name'=>''],['file'=>"",'name'=>'']] file 文件的真实路径 name 显示的文件名
     *              bcc 抄送 只有邮件有抄送 qtalk消息 会将bcc 加入到to 处理
     *          string $type  mail|qtalk 默认是mail
     */

    private static function send(array $data,array $convert_template,string $type='mail'){

        $data['subject'] = static ::convertTemplate($data['subject'],$convert_template);
        $data['body'] = static ::convertTemplate($data['body'],$convert_template);
        if($type == 'mail'){
            return QNotify_Mailer::mail($data['to'],$data['subject'],$data['body'],$data['attach_file'],['html'=>true],$data['bcc']);
        }else if($type =='qtalk'){
            # qtalk 类型不支持附件
            if(isset($data['bcc'])){
                $data['to'][] = $data['bcc'];
            }
            $to = array_unique($data['to']);
            $message = "";
            if($data['subject']){
                $message .= "{$data['subject']}\n";
            }
            if(!empty($data['body'])){
                $message .= $data['body'];
            }
            $message = str_ireplace(["</br>","<br>"],"\n",$message);
            return QNotify_Qtalk::send_single_msg($to,$message);
        }
    }

    /**
     * 积分兑换提醒
     */
    public static function pointExchangeTip($login_user_name){
        global $INI;
        $config = $INI['notify']['point_exchange_tip'];
        $convert_template = ['login_user_name'=>$login_user_name];
        $data = [
            'to'=>$config['to'],
            'subject'=>$config['subject'],
            'body'=>$config['body'],
        ];
        return static::send($data,$convert_template,$config['type']);
    }

    /**
     * 数据库报警
     * @param $title
     * @param $msg
     * @return bool|mixed
     */
    public static function dbAlert($title,$msg){
        # 所有db 类报警均有频率设置
        global $INI;
        $config = $INI['notify']['db_alert'];

        if($config['frequency'] > 0 ){
            $mkey = Mcached::GetStringKey(__CLASS__."::".__FUNCTION__.$title."::".$msg);
            $is_send = Mcached::Get($mkey);
            if(!$is_send){
                Mcached::set($mkey,true,$config['frequency']);//半小时发一次降低发信频率相同的半小时一次
            }else{
                return ;
            }
        }

        $msg .= "</br></br>";
        $msg .= var_export(debug_backtrace(), true);
        $msg .= "</br></br>主机:".gethostname();

        $convert_template = ['title'=>$title,'body'=>$msg];

        $data = [
            'to'=>$config['to'],
            'subject'=>$config['subject'],
            'body'=>$config['body'],
        ];
        return static::send($data,$convert_template,$config['type']);
    }

    public static function apiError($title,$msg){
        global $INI;
        $config = $INI['notify']['api_error'];
        if($config['frequency'] > 0 ){
            $mkey = Mcached::GetStringKey(__CLASS__."::".__FUNCTION__.$title."::".$msg);
            $is_send = Mcached::Get($mkey);
            if(!$is_send){
                Mcached::set($mkey,true,$config['frequency']);//半小时发一次降低发信频率相同的半小时一次
            }else{
                return ;
            }
        }


        $message = "";
        $message .= "_GET:<br>\n";
        $message .= var_export($_GET, TRUE);
        $message .= "_POST:<br>\n";
        $message .= var_export($_POST, TRUE);
        $message .= "<br>\n";
        $message .= "错误信息:".$msg ."<br>";

        $message .= "</br></br>";
        $message .= var_export(debug_backtrace(), true);
        $message .= "</br></br>";
        //注意。以下message不放在md5 key中。， 客户端ip及急服务器ip类都是变化的
        $message.= '服务器ip地址:'.$_SERVER['SERVER_ADDR'].'</br>';
        $message.= '服务器host:'.gethostname().'</br>';
        $message.= 'SERVER_NAME:'.$_SERVER['SERVER_NAME'].'</br>';
        $message.= 'PHP_SELF:'.$_SERVER['PHP_SELF'].'</br>';
        $message.= 'REMOTE_ADDR:'.$_SERVER['REMOTE_ADDR'].'</br>';
        $message.= 'HTTP_X_FORWARDED_FOR:'.$_SERVER['HTTP_X_FORWARDED_FOR'].'</br>';
        $message.= 'HTTP_CLIENT_IP:'.$_SERVER['HTTP_CLIENT_IP'].'</br>';


        $convert_template = ['title'=>$title,'body'=>$message];

        $data = [
            'to'=>$config['to'],
            'subject'=>$config['subject'],
            'body'=>$config['body'],
        ];
        return static::send($data,$convert_template,$config['type']);
    }
    /**
     * 简单 替换body 中的变量值
     *
     * @param $body
     * @param $data array
     */
    private static function convertTemplate(string $body,array $data){
        preg_match_all("/{(.*)}/U",$body,$math);

        foreach($math[0] as $k=>$v){
            $body = str_replace($v,$data[$math[1][$k]],$body);
        }
        return $body;
    }
}