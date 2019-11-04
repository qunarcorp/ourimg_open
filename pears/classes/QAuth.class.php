<?php

/**
 * 登录操作用户相关
 * Class QAuth
 */

class QAuth {

    /**
     * @param array $usernames
     * @return array
     */
    public static function userAvatar(array $usernames)
    {
        $usernameStr = implode(",", array_map(function ($username){
            return "'$username'";
        }, $usernames));

        $sql = "select username, name, img from public.system_user where username in ({$usernameStr})";
        return DBSLAVE::GetQueryResult($sql, false);
    }

    public static function getImgUsername(array $eids)
    {
        $usernameStr = implode(",", array_map(function ($eid){
            return "'$eid'";
        }, $eids));

        $sql = <<<SQL
    select i.eid, u.username as uploader_username, u.name as uploader_realname from public.img i left join public.system_user u on u.username = i.username where i.eid in ({$usernameStr})
SQL;

        return (array) DBSLAVE::GetQueryResult($sql, false);
    }

    /**
     * 获取用户列表
     * @param $whereQuery
     * @param $pageSize
     * @param $currentPage
     * @return array
     */
    public static function getList($whereQuery, $pageSize, $currentPage)
    {

        if($whereQuery){
            $where = " WHERE {$whereQuery} ";
        }

        $countSql = "SELECT count(*) as aggregate FROM public.system_user {$where}";
        $total = intval(DB::GetQueryResult($countSql)["aggregate"]);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $sql = "SELECT id, img, role, username, name, trim(((dept->>0)|| '/'|| (dept->>1)|| '/'|| (dept->>2)  || '/'|| (dept->>3)),'/') as dept 
, create_user, create_time
  FROM system_user
  {$where} ORDER BY id DESC LIMIT {$pageSize} OFFSET {$offset} ";

        $list = DB::GetQueryResult($sql,false);
        return [
            "current_page" => $currentPage,
            "last_page" => $lastPage,
            "page_size" => $pageSize,
            "total" => $total,
            "list" => $list
        ];
    }

    //根据ID跟新用户权限信息Update
    public static function updateByParams($cond,$update) {
        $ret = DB::Update('system_user', $cond, $update);
        return $ret;
    }

    /**
     * 自动更新用户基本信息，姓名，头像
     * @param $username
     * @param $update
     * @return bool|resource
     */
    public static function autoAddUser($username,$update){
        //自动更新用户

        $update_sql = "extend=coalesce(EXCLUDED.extend,'{}'::jsonb)||'{$update['extend']}' ";
        if($update['name']){
            $update_sql .= ",name='{$update['name']}' ";
        }
        if($update['img']){
            $update_sql .= ",img='{$update['img']}' ";
        }

        $sql = "insert into public.system_user (domain_id, role, username, name,img, dept ,extend,create_user, create_time, update_time)
            values ('{$update['domain_id']}','{$update['role']}','{$username}','{$update['name']}','{$update['img']}','{$update['dept']}','{$update['extend']}','system',current_timestamp ,current_timestamp )
            ON CONFLICT ( domain_id,username)  DO UPDATE
              set {$update_sql} ";

        return DB::Query($sql);
    }

    /**
     * 更新用户基本信息，姓名，头像
     * @param $username
     * @param $update
     * @return bool|resource
     */
    public static function adminAddUser($username,$update){
        //自动更新用户
        $sql = "insert into public.system_user (domain_id, role, username, name, img, extend,create_user, create_time, update_time)
            values ('{$update['domain_id']}','{$update['role']}','{$username}','{$update['name']}','{$update['img']}','{$update['extend']}', '{$update['create_user']}',current_timestamp ,current_timestamp )
            ON CONFLICT ( domain_id,username)  DO UPDATE
              set role = '{$update['role']}', update_time = current_timestamp   ";
        return DB::Query($sql);
    }

    //根据用户username和role获取用户信息
    public static function getByParams($cond) {
        $where = '';
        if(is_array($cond)){
            foreach ($cond as $k=>$v){
                $where .= " AND {$k} = '{$v}'";
            }
        }else{
            $where .= ' AND '.$cond;
        }
        $sql = "SELECT id, domain_id, img,role, username, name,dept as dept_arr, trim(((dept->>0)|| '/'|| (dept->>1)|| '/'|| (dept->>2)  || '/'|| (dept->>3)),'/') as dept
, create_user, create_time, update_time, extend, auth_time
  FROM system_user
  WHERE 1=1 {$where} ";

        $ret = DB::GetQueryResult($sql,true);
        if($ret){
            $ret['extend'] = json_decode($ret['extend'],true);
            $ret['role'] = json_decode($ret['role'],true);
            $ret['dept_arr'] = json_decode($ret['dept_arr'],true);
        }
        return $ret;
    }

    //根据条件获取总条数
    public static function getCountByCond($d) {
        $cond = array(
            'select' => 'count(*) as count',
            'condition' => $d,
        );
        $ret = DB::LimitQuery('system_user', $cond);
        return $ret[0]['count'];
    }

    /**
     * @param $username
     * @param $role
     * @return bool
     */
    public static function removePower($username, $role)
    {
        $username = implode(",", array_map(function($item){
            return "'{$item}'";
        }, $username));
        $sql = "UPDATE public.system_user SET role = role::jsonb - '{$role}', update_time = current_timestamp WHERE role::jsonb @> '[\"{$role}\"]'::jsonb AND username in ({$username}) ";
        $result = DB::Query($sql);

        return !! pg_affected_rows($result);
    }


    /**
     * 判断当前用户是否拥有当前api的权限
     * @return bool
     */
    public static function hasPermission()
    {
        global $INI;

        if (self::isSuperAdmin()){
            return true;
        }
        $currentUri = $_SERVER['PHP_SELF'];
        $permissionUri = $INI['permission_uri'];
        $mustPermissionUri = [];
        foreach ($permissionUri as $rolePermission) {
            $mustPermissionUri = array_merge($mustPermissionUri, $rolePermission);
        }
        $mustPermissionUri = array_unique($mustPermissionUri);

        // 判断当前uri是否需要权限验证
        if (in_array($currentUri, $mustPermissionUri)) {
            // 获取用户角色，兼容多角色
            $roles = $_SESSION["login_info"]["role"];
            foreach ($roles as $role){
                // 判断系统是否针对此角色进行权限设置
                if (array_key_exists($role, $permissionUri)){
                    // 判断是否拥有权限
                    return in_array($currentUri, $permissionUri[$role]);
                }
            }

            return false;
        }

        return true;
    }

    /**
     * 判断当前用户是否为管理员
     * @return bool
     */
    public static function isAdmin()
    {
        return in_array("admin", $_SESSION["login_info"]["role"]);
    }

    /**
     * 判断当前用户是否为设计或运营人员
     * @return bool
     */
    public static function isDesign()
    {
        return in_array("design", $_SESSION["login_info"]["role"]);
    }

    /**
     * 判断当前用户是否为超级管理员
     * @param null $username
     * @param null $userDept
     * @return bool
     */
    public static function isSuperAdmin($username = null, $userDept = null)
    {
        global $login_user_name;
        global $INI;
        $username = is_null($username) ? $login_user_name :$username;
        return in_array($username, $INI['auth']['SUPER_ADMIN']);
    }

    /**
     * @param $usernameArr
     * @param $role
     * @return bool
     */
    public static function addPower($username, $role, $system_domain)
    {

        // 获取用户组织架构
        $userDept = DB::GetQueryResult(
            sprintf("select userid, dept from public.employee where userid in (%s) ", implode(",", array_map(function($item){
                return "'{$item}'";
            }, $username))),
            false,
            60
        );

        // 判断是否存在无效用户
        if (count($username) != count($userDept)){
            return false;
        }
        $userDept = array_replace_key($userDept, 'userid');

        $login_auth = new Login();
        foreach ($username as $userId){
            $qtalk_user = $login_auth->getUserInfo($userId);

            $data = [
                'domain_id' => $system_domain,
                'role' => json_encode(["normal", $role], JSON_UNESCAPED_UNICODE),
                'name' => DB::EscapeString($qtalk_user['adname']),
                'img' => DB::EscapeString($qtalk_user['avatar']),
                'dept' => DB::EscapeString(json_encode(
                    array_pad(explode('.', $userDept[$userId]["dept"]), 4, ''),
                    JSON_UNESCAPED_UNICODE)),
                'extend' => json_encode([])
            ];

            $sql = <<<SQL
INSERT INTO public.system_user as su (domain_id, role, username, name,img, dept ,extend,create_user, create_time, update_time)
      values ('{$data['domain_id']}','{$data['role']}','$userId','{$data['name']}','{$data['img']}','{$data['dept']}',
      '{$data['extend']}','{$_SESSION['user_name']}',current_timestamp ,current_timestamp )
      ON CONFLICT ( domain_id,username)  DO UPDATE
      set role = (su.role::jsonb - '$role') || '["$role"]'::jsonb,name='{$data['name']}' ,img='{$data['img']}', dept='{$data['dept']}', update_time = current_timestamp
SQL;
            DB::Query($sql);
        }

        return true;
    }
}