<?php


/**
 * 员工列表类
 * Class QEmployee
 */


class QEmployee
{
    /**
     * 获取员工列表
     * @param $deptId
     * @param $query
     * @param int $currentPage
     * @param int $pageSize
     * @return array
     */
    public static function search($deptId, $query, $currentPage = 1, $pageSize = 20)
    {
        $where = '';
        if ($deptId){
            $where = " WHERE e.dept_id = $deptId";
        }elseif ($query) {
            $where = " WHERE e.userid ~ '$query' OR e.name ~ '$query' ";
        }

        $countSql = "SELECT count(*) as aggregate FROM public.employee e {$where}";
        $total = intval(DB::GetQueryResult($countSql, true, 60)["aggregate"]);
        $lastPage = ceil($total / $pageSize);
        $offset = ($currentPage - 1) * $pageSize;

        $sql = "SELECT e.userid, e.adname, e.avatar, e.dept, su.role FROM public.employee e 
 LEFT JOIN public.system_user as su on e.userid = su.username
 {$where} 
 ORDER BY e.emplid DESC LIMIT {$pageSize} OFFSET {$offset} ";

        $list = DB::GetQueryResult($sql,false, 60);
        return [
            "current_page" => $currentPage,
            "last_page" => $lastPage,
            "page_size" => $pageSize,
            "total" => $total,
            "list" => array_map(function($item) {
                $item["role"] = (array) json_decode($item["role"], true);
                $item["dept"] = str_replace(".", "/", $item["dept"]);
                return $item;
            }, $list)
        ];
    }

    /**
     * 公司组织架构
     * @param int $deptId
     * @param null $role
     * @return array
     */
    public static function companyDept($deptId = 0, $role = null)
    {
        if (empty($role)){
            $managerNumSql = "select count(1) from system_user as su where cd.dept @> trim(((su.dept->>0)|| '.' || (su.dept->>1)|| '.' || (su.dept->>2)  || '.' || (su.dept->>3)), '.')::ltree and '[\"admin\",\"design\"]'::jsonb ?| ('{' || trim(role::text,'[]') || '}')::text[]";
        }else{
            $managerNumSql = "select count(1) from system_user as su where cd.dept @> trim(((su.dept->>0)|| '.' || (su.dept->>1)|| '.' || (su.dept->>2)  || '.' || (su.dept->>3)), '.')::ltree and role::jsonb @> '[\"{$role}\"]'::jsonb";
        }

        $deptSql = sprintf("SELECT cd.id, cd.dept_name, cd.dept, cd.employee_num,
    ($managerNumSql) as manager_num
	FROM public.company_dept as cd where parent_id = %s ORDER BY id asc", intval($deptId));

        $deptList = DB::GetQueryResult($deptSql,false, 60);

        $employeeSql = sprintf("SELECT e.userid, e.adname, e.avatar, e.dept, su.role FROM public.employee e
 left join public.system_user as su on e.userid = su.username
 where dept_id = %s  ORDER BY emplid DESC ", intval($deptId));

        $employeeList = DB::GetQueryResult($employeeSql,false, 60);
        return [
            "employee" => array_map(function ($item) {
                $item["role"] = (array) json_decode($item["role"], true);
                return $item;
            }, $employeeList),
            "dept" => $deptList,
        ];
    }

    /**
     * 员工数量和管理员数量
     * @return array
     */
    public static function employeeAndManagerNum()
    {
        $countSql = "SELECT 
(SELECT count(*) FROM public.employee) as employee_aggregate,
(SELECT count(*) FROM public.system_user where role::jsonb @> '[\"admin\"]'::jsonb) as admin_aggregate,
(SELECT count(*) FROM public.system_user where role::jsonb @> '[\"design\"]'::jsonb) as design_aggregate,
(SELECT count(*) FROM public.system_user where '[\"admin\",\"design\"]'::jsonb ?| ('{' || trim(role::text,'[]') || '}')::text[]) as manager_aggregate";

        return DB::GetQueryResult($countSql, true, 60);
    }

    /**
     * 员工头像缓存
     * @param $userIds
     * @return array
     */
    public static function employeeAvatar($userIds)
    {
        $userIds = implode(",", array_map(function($userId){
            return "'$userId'";
        }, $userIds));

        $userAvatar = DB::GetQueryResult("select userid, avatar from public.employee where userid in ({$userIds})", false, 60);

        return array_map(function($item){
            return $item["avatar"];
        }, array_replace_key($userAvatar, 'userid'));
    }

    /**
     * 获取qtalk用户头像
     * @param $userId
     * @return mixed
     */
    public static function getEmployeeAvatar($userId)
    {
        $login_auth = new Login();
        $qtalk_user = $login_auth->getUserInfo($userId);
        return $qtalk_user['avatar'];
    }
}