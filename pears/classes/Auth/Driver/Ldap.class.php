<?php

/**
 * qsso 登录认证
 * Class Auth_Driver_Qsso
 */

class Auth_Driver_Ldap implements Auth_Interface
{
    private $config = [];
    private $system_domain = "";
    private $default_vcard = "";

    private $conn   = "";
    public function __construct($system_domain,$config)
    {
        $this->config = $config['ldap'];
        $this->system_domain = $system_domain;
        $this->default_vcard = $config['default_vcard'];
    }

    public function getLoginUserName()
    {
        return ["usernmae"=>$_SESSION['user_name']??"",'need_setsession'=>false];
    }

    /**
     * 连接ldap 服务器 按需链接 。不直接初始化
     * @throws Exception
     */
    private function ldap_conn(){
        if(!$this->conn){
            $this->conn = ldap_connect($this->config['server_ip'],$this->config['server_port']);

            if(!$this->conn){
                throw new Exception("Auth driver ldap cont connect to {$this->config['server_ip']}",500);
            }
            ldap_set_option($this->conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        }
    }

    /**
     * 验证ldap 用户信息
     * @param $username
     * @param $password
     * @return bool
     * @throws Exception
     */
    private function ldap_bind($username,$password){
        $this->ldap_conn();
        $ret = @ldap_bind($this->conn, $username .$this->config['user_region'],$password);
        return $ret;
    }

    /**
     * @param $query 搜索 支持 * 例suozhu*
     * @return array
     * @throws Exception
     *
     */
    private function ldap_search($query){
        $filter = "{$this->config['username_column']}={$query}";

        $cache_key = md5(__CLASS__."::".$filter);
        $result = Mcached::get($cache_key);
        if(!$result){
            $res = ldap_search($this->conn, $this->config['base_dn'], $filter,$this->config['columns']);
            if(!$res){
                throw new Exception(ldap_error($this->conn),ldap_errno($this->conn));
            }
            $data = ldap_get_entries($this->conn, $res);
            $result = [];
            for($i=0;$i<$data['count'];$i++) {
                $result[] = [
                    "userid"=>$data[$i][$this->config['username_column']][0],
                    "adname"=>$data[$i]['cn'][0],
                    "dept"=>str_replace('\\','/', $data[$i]['department'][0]),
                    "dept_arr"=>explode('\\', $data[$i]['department'][0]),
                    'avatar' => $this->default_vcard,
                    "company"=>"",
                ];
            }
            Mcached::set($cache_key,$result,500);
        }
        return $result;
    }

    /**
     * 登录认证
     * @param $username
     * @param $password
     * @return bool|mixed
     * @throws Exception
     */
    public function auth($username, $password)
    {
        if(!$this->ldap_bind($username,$password)){
            return false;
        }

        return $this->getUserInfo($username);
    }


    /**
     * 注意 此方法权授权用户 再次获取可使用，走缓存， 其他情况无法使用
     * 如果想用，可更改 像searchUser一样。先ldap_bind 在搜索
     * 调用此方法 一般 只有auth 以后会调用，
     */
    public function getUserInfo($username)
    {
        $data = $this->ldap_search($username);
        if(!$data){
            return false;
        }
        return $data[0];
    }

    /**
     * 搜索 用户时使用帐户 默认使用配置中的搜索 用户， 如果没有配置， 则使用登录的用户的
     * @return bool
     * @throws Exception
     */
    private function searchUserBind(){
        # 搜索 必须先签权才能搜索
        if(!$this->ldap_bind($this->config['search_user']?:$_SESSION['login_username'],$this->config['search_user']?:$_SESSION['login_password'])){
            return false;
        }
        return true;
    }

    /**
     * 使用 employee 接口缓存
     * @param $username
     * @return mixed|void
     */
    public function searchUser(array $params)
    {
        # 搜索 必须先签权才能搜索
        if(!$this->searchUserBind()){
            return false;
        }

        if(strlen($params['username']) <3 && !preg_match("/\./",$params['username'])){
            return false;
        }

        $data = $this->ldap_search($params['username']."*");
        if(!$data){
            return false;
        }
        $res_arr = [];
        foreach($data as $k=>$v){
            if(isset($params['pageSize']) && $k>= $params['pageSize']){
                break;
            }
            /**
             * 获取本地用户头像、权限组
             */
            $cond = array(
                'domain_id' => $this->system_domain,
                'username' => $v['userid'],
            );
            $local_user = QAuth::getByParams($cond);
            $v['role'] = $local_user['role'];
            if(empty($v['role'])){
                $v['role'] = [];
            }
            if($local_user['avatar']){
                $v['avatar'] = $local_user['avatar'] ;
            }
            $res_arr[$k] = $v;
        }
        return [
            "current_page" => $params['currentPage'],
            "last_page" => $params['currentPage'],
            "page_size" => $params['pageSize'],
            "total" => count($res_arr),
            "list" => $res_arr
        ];
    }

}