<?php
/**
 * 下载权限控制
 */

class QDownloadAuth {
    /*
     * 用户权限控制
     */
    public static function checkUserAuth($params=[]){
        $upload_username = $params['upload_username'] ? $params['upload_username'] : '';
        $download_username = $params['download_username'] ? $params['download_username'] : '';
        $img_arr = $params['img_arr'] ? $params['img_arr'] : [];
        
        //获取图片用户
        if( !$upload_username ){
            $upload_usernames = array_unique(array_column($img_arr, 'username'));
        
            //如果是自己有权限
            if( count($upload_usernames) == 1 && $upload_usernames[0] == $download_username ){
                return true;
            }
        }else{
            if( $upload_username == $download_username ){
                return true;
            }
        }
        
        
        //验证下载用户是超级管理员\验证下载用户是管理员、设计运营角色
        $userInfo = QImgPersonal::getUserInfo(['username'=>$download_username]);
        if( array_intersect(['design','admin','super_admin'], $userInfo['role']) ){
            return true;
        }
        
        return false;
    }
    
    /*
     * 老的返回格式
     */
    public static function returnAuthCheck($params=[]){
        $callback = $params['callback'] ? $params['callback'] : '';//callback
        $check_rs = self::checkUserAuth($params);
        if(!$check_rs){
            $rs = [
                "ret" => false,
                "msg" => "申请开通图片下载权限，请发邮件，注明申请原因",
            ];
            display_json_str_common($rs, $callback);
        }
    }
    /*
     * 新的返回格式
     */
    public static function returnAuthCheckNew($params=[]){
        $callback = $params['callback'] ? $params['callback'] : '';//callback
        $check_rs = self::checkUserAuth($params);
        if(!$check_rs){
            $rs = [
                "status" => 112,
                "message" => "申请开通图片下载权限，请发邮件，注明申请原因",
            ];
            display_json_str_common($rs, $callback);
        }
    }
}
