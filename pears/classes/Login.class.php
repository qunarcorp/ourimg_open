<?php

/**
 * 认证基础类 用于登录
 * Class Auth
 *
 */

class Login implements Auth_Interface
{
    /**
     * 存储适配器类
     * @var string
     */
    protected $driver = "";
    protected $system_domain = "";

    protected $instance = "";


    protected $auto_update_local_user = false;
    protected $auto_update_local_user_time = "+1 day";

    public function __construct()
    {
        global $INI;
        global $system_domain;

        $this->driver = $INI['auth']['driver'];
        $this->auto_update_local_user = $INI['auth']['auto_update_local_user'];
        $this->auto_update_local_user_time = $INI['auth']['auto_update_local_user_time'];

        $this->system_domain = $system_domain;

        # driver class 组成
        $driver_class = "Auth_Driver_".ucwords($this->driver);

        if(!class_exists($driver_class)){
            throw new Exception("Auth driver not exists",500);
        }

        $this->instance = new $driver_class($system_domain,$INI['auth']);
    }

    /**
     * 获取登录用户，
     * @return mixed
     * @throws Exception
     */
    public function getLoginUserName(){

        global $login_user_name;
        if($login_user_name){
            return $login_user_name;
        }else if($_SESSION['user_name']){
            return $_SESSION['user_name'];
        }else{
            # 需要特殊处理的 ，二次更改session的。qt一键登录
            $result = $this->instance->getLoginUserName();
            if(!empty($result['usernmae']) && $result['need_setsession']){
                $rs = $this->setSession($user=['userid'=> $result['usernmae']]);
                if(!$rs){
                    throw new Exception("reset session user info failed",500);
                }
            }
            return $result['usernmae'];
        }
    }

    /**
     * 获取登录的真实姓名
     * @return mixed|string
     */
    public function getLoginName(){
        return $_SESSION['real_name']??"";
    }

    /**
     * 获取登录的用户全部信息
     * @return mixed|string
     */
    public function getLoginInfo(){
        return $_SESSION['login_info']??"";
    }

    /**
     * 获取登录方式
     * @return string
     */
    public function getLoginDriver(){
        $callback = filter_input(INPUT_GET,'callback');
        $json = array("status"=>0,"message"=>"获取成功","data"=>$this->driver);
        display_json_str_common($json,$callback);
    }

    /**
     * 登录流程
     */
    public function login(){
        # qsso 登录认证token
        if('qsso' == $this->driver){
                $token = filter_input(INPUT_POST,"token");
                $user_url = filter_input(INPUT_GET,"user_url");

                if(empty($token)){
                    redirect($user_url,4,"参数错误");
                }
                $user_url = $user_url ? : "/";
                $user = $this->auth("",$token);
                if(!$user){
                    redirect($user_url,4,"登录失败");
                }
        }else{
                $username = filter_input(INPUT_POST,"username");
                $password = filter_input(INPUT_POST,"password");
                $callback = filter_input(INPUT_GET,'callback');

                if(empty($username) || empty($password)){
                    $json = array("status"=>-1,"message"=>"参数错误","data"=>(object)[]);
                    display_json_str_common($json,$callback);
                }
                $user = $this->auth($username,$password);
                if(!$user){
                    $json = array("status"=>-1,"message"=>"登录失败","data"=>(object)[]);
                    display_json_str_common($json,$callback);
                }
                # 除qsso 登录以外的 都记录 用户名密码 ldap 记录 用于二次查询 ，或查询其他用户
                $user['login_username'] = $username;
                $user['login_password'] = $password;
        }

        $result = $this->setSession($user);

        if('qsso' == $this->driver) {
            if($result){
                redirect($user_url, 0);
            }else{
                redirect($user_url, 2, "登录失败");
            }
        }else{
            if($result){
                $json = array("status"=>0,"message"=>"登录成功","data"=>(object)[]);
            }else{
                $json = array("status"=>-1,"message"=>"登录失败","data"=>(object)[]);
            }
            display_json_str_common($json,$callback);
        }

    }

    /**
     * api 检测登录， 如果没登录 直接返回json
     */
    public function apiCheckLogin(){
        global $login_user_name;
        $callback = filter_input(INPUT_GET,"callback");
        if(!$login_user_name){
            $rs = [
                "status" => 100,
                "message" => "用户登录信息有误",
            ];
            display_json_str_common($rs, $callback);
        }
    }
    /**
     * 退出登录
     */
    public function loginOut(){
        $callback = filter_input(INPUT_GET, 'callback', FILTER_SANITIZE_STRING);
        $this->cleanSession();
        $json = array("status"=>0,"message"=>"退出成功","data"=>[]);
        display_json_str_common($json,$callback);
    }

    /**
     * 获取本地注册用户，不存在自动注册一下
     * @param $user
     * @return array|bool
     */
    private function getLocalUser($user){

        /**
        * 获取本地用户
        */
        $cond = array(
            'domain_id' => $this->system_domain,
            'username' => $user['userid'],
        );
        $local_user = QAuth::getByParams($cond);


        # 不存在本地用户， 或者启用自动更新用户 触发更新
        if(empty($local_user) || empty($local_user['name']) || empty($local_user['dept']) ||

            ($this->auto_update_local_user && $local_user['extend']['expiry_time'] > time() )){
            $user_info = $this->getUserInfo($user['userid']);
            $update = [];
            $update['domain_id'] = $this->system_domain;
            $update['role'] = '["normal"]';

            $update['name'] = DB::EscapeString($user['adname']??$user_info['adname']);

            if($user_info['avatar'] && empty($local_user['img'])){
                $update['img'] = DB::EscapeString($user_info['avatar']);
            }

            $dept = $user['dept_arr']?:$user_info['dept_arr'];
            if(empty($dept)){
                $dept = [];
            }
            $update['dept'] = DB::EscapeString(json_encode($dept,JSON_UNESCAPED_UNICODE));

            $extend = ["company"=>$user['company']];
            if($this->auto_update_local_user){
                $extend['expiry_time'] = strtotime($this->auto_update_local_user_time);
                $extend['expiry_date'] = date("Y-m-d H:i:s",strtotime($this->auto_update_local_user_time));
            }

            $update['extend']  = DB::EscapeString(json_encode($extend,JSON_UNESCAPED_UNICODE));

            if(!QAuth::autoAddUser($user['userid'],$update)){
                return false;
            }

            $cond = [
                'domain_id' => $this->system_domain,
                'username' => $user['userid'],
            ];
            $local_user = QAuth::getByParams($cond);
        }
        return $local_user;
    }

    /**
     * 登录相关session
     * @param $user
     * @param $local_user
     */
    private function setSession($user){

        $local_user = $this->getLocalUser($user);

        if(!$local_user){
            return false;
        }
        $_SESSION['is_login'] = true;
        $_SESSION['user_name'] = $local_user['username'];
        $_SESSION['login_username'] = $user['login_username'];
        $_SESSION['login_password'] = $user['login_password'];
        $_SESSION['real_name'] = $local_user['name'];
        $_SESSION['login_info'] = $local_user;
        setcookie('_USERNAME', $local_user['username'], 0, "/", '', false, false);
        setcookie('_REALNAME', $local_user['name'], 0, "/", '', false, false);
        return true;
    }

    /**
     * 清除灯录信息
     */
    private function cleanSession(){

        unset($_SESSION['is_login']);
        unset($_SESSION['user_name']);
        unset($_SESSION['login_username']);
        unset($_SESSION['login_password']);
        unset($_SESSION['real_name']);
        unset($_SESSION['login_info']);

        setcookie('_USERNAME', "", time()+10, "/", '', false, false);
        setcookie('_REALNAME', "", time()+10, "/", '', false, false);
    }

    /**
     * 认证 用户名密码
     * @return mixed
     */
    public function auth($username, $password)
    {
        return $this->instance->auth($username, $password);
    }

    /**
     * 仅用于auth 内部使用
     * @param $username
     * @return mixed
     */
    public function getUserInfo($username)
    {
        return $this->instance->getUserInfo($username);
    }
    public function searchUser(array $params)
    {
        return $this->instance->searchUser($params);
    }
}