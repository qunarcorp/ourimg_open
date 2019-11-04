<?php

/**
 *
 * pg数据库斟础类
 */
class QDB {

    public static $mInstance = null;   //子类需要声明
    public static $mConnection = null; //子类需要声明
    public static $mError = null;   //子类需要声明
    public static $mDebug = false;
    public static $mCount = 0;      //子类需要声明
    public static $mResult;         //子类需要声明
    public static $dbConfig = ''; //该选项如果db类名称与db类不
    public static $mDefaultSchema = ''; //for insert update delete  GetDbRowById LimitQuery UpdateToCount 一些保留字的表查询会有问题 如user表。 需要public.user
    public static $mSearchPath = '';//设置默认的schema搜索路径
    function __construct() {

    }

    /**
     * 禁止clone
     */
    private function __clone() {

    }

    /**
     * 方法重载 当调用不存在的方法时调用
     * @param string $name  方法名
     * @param array $arguments 对应的参数数组
     */
    public function __call($name, $arguments) {
        self::mail("调用了不存在的方法[{$name}]", "参数:" . implode(', ', $arguments));
    }

    /**
     *  PHP 5.3.0之后版本
     * 方法重载 当调用不存在的方法时调用
     * @param string $name  方法名
     * @param array $arguments 对应的参数数组
     *
     */
    public static function __callStatic($name, $arguments) {
        self::mail("调用了不存在的静态方法[{$name}]", "参数:" . implode(', ', $arguments));
    }

    /**
     * 统一报警处理
     * @param $title
     * @param $msg
     */
    public static function mail($title, $msg) {
        QNotify::dbAlert($title,$msg);
    }

    /**
     * 记录查询慢的日志
     * @param type $sql
     */
    public static function SqlLog($sql, $action = 'slow',$uniq_sql='') {
        $log_message = str_replace("\n",' ', $sql . ";page=>" . $_SERVER['PHP_SELF']);
        QLog::info('sql', $action, $log_message);
        if(preg_match("/duplicate key value/", $sql) || $action=='slow'){
            //用户重复插入报警，不在报了。
            return ;
        }
        //只记记录，不报警了
        return ;
    }

    /**
     * 单例
     * @param  string $db  QDB 类调用时管用
     * 返回类
     */
    public static function &Instance() {

        $class = get_called_class();
        if ($class == "QDB") {
            $message = "QDB class 不支持单独使用,请使用QDB子类";
            self::mail($message);
            throw new Exception($message);
            exit; //不存在数据库配置的时候必须退出~
        }
        if (!(static::$mInstance instanceof $class)) {
            //每个类只初始化一次类只初始化一次
            static::$mInstance = new $class ();
        }
        //pg 连接根据不同的类调用，初始化不同的数据库，并加入判断连接断开->重连，连接错误->重连
        self::connect(); //连接数据库
        return static::$mInstance;
    }

    /**
     * 连接数据库
     * pg 连接根据不同的类调用，初始化不同的数据库，并加入判断连接断开->重连，连接错误->重连
     */
    public static function connect() {
        global $INI;
        $db_config = static::$dbConfig;
        if (!is_resource(static::$mConnection)) {
            if (!isset($INI['db_config'][$db_config])) {
                self::mail($db_config."不存在");
                throw new Exception("db_config[{$db_config}] not found");
                exit; //不存在数据库配置的时候必须退出~
            } else {
                $host = (string) $INI ['db_config'] [$db_config] ['host'];
                $user = (string) $INI ['db_config'] [$db_config] ['user'];
                $pass = (string) $INI ['db_config'] [$db_config] ['pass'];
                $name = (string) $INI ['db_config'] [$db_config] ['name'];
                //数据库链接默认5432可手工指定
                $port = (int) (isset($INI ['db_config'] [$db_config] ['port'])?$INI ['db_config'] [$db_config] ['port'] :5432);

                //只要数据库连接有一点不一样的地方就会新建一个pg链接,否则是同一个链接
                static::$mConnection = pg_connect("host={$host} port={$port} dbname={$name} user={$user} password={$pass}");

                if (false === static::$mConnection) {
                    $message = "pg:{$db_config} Connect failed";
                    self::mail($message,$message);
                    throw new Exception($message);
                } else if (pg_last_error(static::$mConnection)) {
                    $message = "Connect failed: " . pg_last_error(static::$mConnection);
                    self::mail($db_config.' Connect failed ',$message);
                    throw new Exception($message);
                }
                if(!empty(static::$mSearchPath)){
                    pg_query(static::$mConnection, "set search_path to ".static::$mSearchPath.";");
                }
            }
        } else {
            if (pg_connection_status(static::$mConnection) === PGSQL_CONNECTION_BAD ) {
                if(!pg_connection_reset(static::$mConnection)){
                    $message = "pg reconnection:{$db_config} Connect failed";
                    self::mail($message,$message);
                    throw new Exception($message);
                }
                /**
                 * 重置连接的需 要重新set search path
                 */
                if(!empty(static::$mSearchPath)){
                    pg_query(static::$mConnection, "set search_path to ".static::$mSearchPath.";");
                }
            }
        }
    }

    /**
     * 获取数据库链接
     * @return pg resource
     */
    static public function GetLinkId() {
        static::Instance();
        return static::$mConnection;
    }

    function __destruct() {
        self::Close();
    }

    /**
     * 关闭pg连接，当前不关
     * @return type
     */
    static public function Close() {
        return;
    }

    /**
     * 开始事务
     * @param integer $level    隔离级别   默认1
     *                          1=SERIALIZABLE 可串行化
     *                          2=REPEATABLE READ 可重复读
     *                          3=READ COMMITTED 读已提交
     *                          4=READ UNCOMMITTED 读未提交
     * @return  boolean|resource
     *
     */
    public static function TransBegin($level = 1) {
        $level_value = 'BEGIN;SET transaction ISOLATION LEVEL  ';
        switch ($level) {
            case 1:
                $level_value .= " SERIALIZABLE "; //可串行化
                break;
            case 2:
                $level_value .= " REPEATABLE READ "; //可重复读
                break;
            case 3:
                $level_value .= " READ COMMITTED "; //读已提交
                break;
            case 4:
                $level_value .= " READ UNCOMMITTED "; //读未提交
                break;
            default :
                $level_value .= " SERIALIZABLE "; //可串行化
        }
        $level_value .= ";";
        return self::Query($level_value);
    }

    /**
     * 事务回滚
     * @return boolean|resource
     */
    public static function TransRollback() {
        return self::Query("ROLLBACK;");
    }

    /**
     * 事务提交
     * @return boolean|resource
     */
    public static function TransCommit() {
        return self::Query("COMMIT;");
    }


    /**
     * 转义字符
     * @param string $string    要转义的字符串
     * @return pg_escape_string($string)
     */
    static public function EscapeString($string) {
        static::Instance();
        return pg_escape_string($string);
    }

    /**
     * 获取插处语句的最后插入值
     * @param string $return 返回的字段名
     * @return type
     */
    static public function GetInsertId($return = 'id') {
        static::Instance();
        $row = pg_fetch_assoc(static::$mResult);
        $return = trim($return);
        if ($return == '*') {
            $return = 'id';
        }
        return $row[$return];
    }

    /**
     * 查询sql并返回查询结果resource
     * @param string $sql
     * @param boolean $nestloop  true 为关闭，默认是打开状态 关闭规划器对嵌套循环连接规划类型的使用
     * @return boolean|resource
     */
    static public function Query($sql, $nestloop = false) {
        $start = microtime(true);
        static::Instance();

        if (static::$mDebug) {
            echo $sql . "\n";
        }
        if ($nestloop)
            pg_query(static::$mConnection, "set enable_nestloop to off;");
        $result = pg_query(static::$mConnection, $sql);
        if ($nestloop)
            pg_query(static::$mConnection, "set enable_nestloop to on;");
        static::$mCount ++;
        $spend = microtime(true) - $start;
        if ($spend > 10) {
            self::SqlLog("time:" . $spend . ";sql:" . $sql, 'slow',$sql);
        }

        //QLog::info(1,2,$sql);
        if ($result) {
            return $result;
        } else {
            static::$mError = pg_last_error(static::$mConnection);
            self::SqlLog("sql:" . $sql . ";error:" . static::$mError, 'failed',$sql);
        }
        return false;
    }

    /*
     * 查询预处理
     *
     * @param $sql 执行sql
     * @param $bind 参数数组
     * @param $nestloop 执行nestloop语句
     *
     * 事例：
     * $aParam = array("18623","partner_shop","1");
     * $rs = DB::QueryPrepare("select * from vendor_bank_info where type_id = $1 and type = $2 and status = $3;",$aParam);
     *
     */

    static public function QueryPrepare($sql, $bind = array(), $nestloop = false) {
        $start = microtime(true);
        static::Instance();
        if (!strstr($sql, "\$")) {
            $aParam = array();
            $i = 1;
            foreach ($bind as $key => $row) {
                $aParam[] = $row["value"];
                $sql = str_replace($key, "\${$i}", $sql);
                $i++;
            }
        } else {
            $aParam = $bind;
        }
        if (static::$mDebug) {
            echo $sql . "\n";
            var_dump($aParam);
        }
        if ($nestloop) {
            pg_query(static::$mConnection, "set enable_nestloop to off;");
        }
        $prepare_rs = pg_prepare(static::$mConnection, "", $sql);
        if ($prepare_rs == false) {
            $result = false;
        } else {
            $result = pg_execute(static::$mConnection, "", $aParam);
        }
        if ($nestloop) {
            pg_query(static::$mConnection, "set enable_nestloop to on;");
        }

        static::$mCount ++;
        $spend = microtime(true) - $start;
        if ($spend > 10) {
            self::SqlLog("time:" . $spend . ";sql:" . $sql, 'slow',$sql);
        }

        if ($result) {
            return $result;
        } else {
            static::$mError = pg_last_error(static::$mConnection);
            self::SqlLog("sql:" . $sql . ";error:" . static::$mError, 'failed',$sql);
        }
        return false;
    }

    /**
     * 获取下一条记录
     * @param resource $query pg查询结果
     * @return array 键值全为小写
     */
    static public function NextRecord($query) {
        return array_change_key_case(pg_fetch_assoc($query), CASE_LOWER);
    }

    /**
     * 查询某表指定条件的一条查询结果
     * @param string $table 表名
     * @param array $condition 条件
     * @return array
     */
    static public function GetTableRow($table, $condition, $no_public = false) {
        return self::LimitQuery("{$table}", array('condition' => $condition, 'one' => true), $no_public = false);
    }

    /**
     * 查询表内id结果
     * @param string $table 表名
     * @param array $ids
     * @return array 返回以id为键的数组
     */
    static public function GetDbRowById($table, $ids = array()) {
        $one = is_array($ids) ? false : true;
        settype($ids, 'array');
        $idstring = join('\',\'', $ids);
        //$idstring = self::EscapeString($idstring);

        if (preg_match('/[\s]/', $idstring)) {
            return array();
        }
        $q = "SELECT * FROM ".static::$mDefaultSchema."{$table} WHERE id IN ('{$idstring}')";
        $r = self::GetQueryResult($q, $one);
        if ($one) {
            return $r;
        }

        return Utility::AssColumn($r, 'id');
    }

    /**
     * 查询某表，根据不同条件，查询多条 多用于分页
     * @param string $table 表名
     * @param array $options
     * @param type $no_public  默认false 是否在表名前加"public."
     * @return array  查询返回结果
     */
    static public function LimitQuery($table, $options = array(), $no_public = false) {

        $condition = isset($options ['condition']) ? $options ['condition'] : null;
        $one = isset($options ['one']) ? $options ['one'] : false;
        $offset = isset($options ['offset']) ? abs(intval($options ['offset'])) : 0;
        if ($one) {
            $size = 1;
        } else {
            $size = isset($options ['size']) ? abs(intval($options ['size'])) : null;
        }
        $select = isset($options ['select']) ? $options ['select'] : '*';
        $order = isset($options ['order']) ? $options ['order'] : null;
        $cache = isset($options ['cache']) ? abs(intval($options ['cache'])) : 0;

        $condition = self::BuildCondition($condition);
        $condition = (null == $condition) ? null : "WHERE   " . $condition;

        $limitation = $size ? "LIMIT $size OFFSET $offset" : null;
        if ($no_public) {
            $sql = "SELECT {$select} FROM {$table} $condition $order $limitation";
        } else {
            $sql = "SELECT {$select} FROM ".static::$mDefaultSchema."{$table} $condition $order $limitation";
        }
        return self::GetQueryResult($sql, $one, $cache);
    }

    /**
     * 获取sql语句查询结果
     * @param string $sql
     * @param boolean $one 默认true
     * @param boolean $cache
     * @param boolean $nestloop
     * @param  $dberror 引用 $dberror变量
     * @return array
     */
    static public function GetQueryResult($sql, $one = true, $cache = 0, $nestloop = false, &$dberror = '') {

        if ($cache > 0) {
            $mkey = Mcached::GetStringKey($sql.$one);
            $ret = Mcached::Get($mkey);
            if ($ret) {
                return $ret;
            }
        }
        $ret = array();
        if ($result = self::Query($sql, $nestloop)) {
            while ($row = pg_fetch_assoc($result)) {
                $row = array_change_key_case($row, CASE_LOWER);
                if ($one) {
                    $ret = $row;
                    break;
                } else {
                    array_push($ret, $row);
                }
            }
            if($cache >0){
                Mcached::Set($mkey, $ret, 0, $cache);
            }
            pg_free_result($result);
        }
        if (!empty(static::$mError)) {
            $dberror = static::$mError;
        }
        return $ret;
    }

    /**
     * 使用某个key做索引 ，默认key 为id
     * @param string $sql
     * @param boolean $one 默认true
     * @param boolean $cache
     * @param boolean $nestloop
     * @param  $dberror 引用 $dberror变量
     * @param string $key 返回数组的key 如果一条查询则key失效
     * @return array
     */
    static public function GetQueryResultNoAssoc($sql, $one = true, $cache = 0, $nestloop = false, &$dberror = '', $key = "id") {
        if ($cache > 0) {
            $mkey = Mcached::GetStringKey(serialize(func_get_args()));
            $ret = Mcached::Get($mkey);
            if ($ret) {
                return $ret;
            }
        }
        $ret = array();
        if ($result = self::Query($sql, $nestloop)) {
            while ($row = pg_fetch_assoc($result)) {
                if ($one) {
                    $ret = $row;
                    break;
                } else {
                    $id = $row[$key];
                    $ret[$id] = $row;
                }
            }

            pg_free_result($result);
        }
        if ($ret) {
            if ($cache > 0) {
                Mcached::Set($mkey, $ret, 0, $cache);
            }
        }
        if (!empty(static::$mError)) {
            $dberror = static::$mError;
        }
        return $ret;
    }

    /*
     * 获取预处理结果类
     * @param $sql 执行sql
     * @param $bind 参数数组
     * @param $one 是否取一条记录
     * @param $cache 是否cache
     * @param $nestloop 执行nestloop语句
     *
     * 事例：
     * $aParam = array("18623","partner_shop","1");
     * $rs =xxDB::GetQueryResultPrepare("select * from vendor_bank_info where type_id = $1 and type = $2 and status = $3;",$aParam);*
     *
     */

    static public function GetQueryResultPrepare($sql, $bind = array(), $one = true, $cache = 0, $nestloop = false) {
        if ($cache > 0) {
            $mkey = Mcached::GetStringKey(serialize(func_get_args()));
            $ret = Mcached::Get($mkey);
            if ($ret) {
                return $ret;
            }
        }
        $ret = array();
        if ($result = self::QueryPrepare($sql, $bind, $nestloop)) {
            while ($row = pg_fetch_assoc($result)) {
                $row = array_change_key_case($row, CASE_LOWER);
                if ($one) {
                    $ret = $row;
                    break;
                } else {
                    array_push($ret, $row);
                }
            }

            pg_free_result($result);
        }
        if ($ret) {
            if ($cache > 0) {
                Mcached::Set($mkey, $ret, 0, $cache);
            }
        }

        return $ret;
    }

    /**
     * Insert 方法别名
     * @param string $table
     * @param array $condition 要插入的数组
     * @return int  插入表的 GetInsertId
     */
    static public function SaveTableRow($table, $condition, $return = '*') {
        return self::Insert($table, $condition,$return);
    }

    /**
     * 入库转换
     * @param $v
     * @return string|null
     */
    static public function CovertStr($v){
        $v_str = null;
        if (is_numeric($v)) {
            $v_str = "'{$v}'";
        }else if (is_null($v) || $v == '') {
            $v_str = 'NULL';
        } else if (is_array($v)) {
            $v [0] = self::EscapeString($v [0]);
            $v_str = $v [0]; //for plus/sub/multi;
        } else {
            $v_str = "'" . self::EscapeString($v) . "'";
        }

        return $v_str;
    }

    /**
     * 插入表， 支持自定义返回字段，
     * @param string $table
     * @param array $condition 要插入的数组
     * @param string $return  组合sql语句，如果为空则不使用return ,如果要返回id 则写'id'
     * @return boolean
     *
     * Insert($table, $condition,$return='id')
     */
    static public function Insert($table, $condition, $return = '*') {
        static::Instance();
        $sql = "INSERT INTO ".static::$mDefaultSchema."{$table} ";

        $content = null;
        $columnlist = null;
        $valuelist = null;

        $columnlist .= '(';
        $valuelist .= '(';
        foreach ($condition as $k => $v) {
            $v_str = self::CovertStr($v);
            $columnlist .= "$k,";
            $valuelist .= "$v_str,";
        }
        $columnlist = trim($columnlist, ',');
        $valuelist = trim($valuelist, ',');
        $columnlist .= ')';
        $valuelist .= ')';

        $content = $columnlist . ' VALUES ' . $valuelist;
        if (!empty($return)) {
            if(trim($return) =='*'){
                $debugs =  debug_backtrace();
                $debug =$debugs[0];
                for($i=0;$i<count($debugs);$i++){
                    if(stripos($debugs[$i]['file'],'db.class')===false){
                        $debug = $debugs[$i];
                        break;
                    }
                }
                $mkey_string = static::$dbConfig."::".static::$mDefaultSchema."::".$table.$debug['file']."::".$debug['line'];
                $mkey = Mcached::GetStringKey($mkey_string);
                $mret = Mcached::Get($mkey);
                if(!$mret){
                    self::SqlLog("line:".$debug['line'].";".$debug['file'],"insert",$sql);
                    Mcached::set($mkey,true,86400);
                    unset($debug);
                }
            }
            $content .= " RETURNING " . $return;
        }
        $sql .= $content;

        $result = self::Query($sql);
        static::$mResult = $result;

        if (false == $result) {
            self::Close();
            return false;
        }
        if($return){
            ($insert_id = self::GetInsertId($return)) || ($insert_id = true);
        }else{
            $insert_id = true;
        }
        return $insert_id;
    }

    /**
     * Delete别名
     * @param string $table 表名
     * @param array $condition 要删除的条件数组
     * @return boolean
     */
    static public function DelTableRow($table = null, $condition = array()) {
        return self::Delete($table, $condition);
    }

    /**
     * 删除符合条件的数据
     * @param string $table 表名
     * @param array $condition 要删除的条件数组
     * @return mixd boolean | resource
     */
    static public function Delete($table = null, $condition = array()) {
        if (null == $table || empty($condition)) {
            return false;
        }
        static::Instance();

        $condition = self::BuildCondition($condition);
        $condition = (null == $condition) ? null : "WHERE $condition";
        $sql = "DELETE FROM ".static::$mDefaultSchema."$table $condition";
        return self::Query($sql);
    }

    /**
     * 更新表内容
     * @param string $table 表名
     * @param array $condition 要删除的条件数组
     * @param array|string $id  array 则组合condition |string 则 $pkname 对应的值组合sql结果 id=1
     * @param array  $updaterow 更新的字段数组
     * @param string $pkname  前置 $id 为字符串时 ，此为更新的字段名
     * @return boolean
     */
    static public function Update($table = null, $id = 1, $updaterow = array(), $pkname = 'id') {

        if (null == $table || empty($updaterow) || null == $id) {
            return false;
        }

        if (is_array($id)) {
            $condition = self::BuildCondition($id);
        } else {
            $condition = "$pkname='" . self::EscapeString($id) . "'";
        }

        static::Instance();

        $sql = "UPDATE ".static::$mDefaultSchema."{$table} SET ";

        $content = null;

        foreach ($updaterow as $k => $v) {
            $v_str = self::CovertStr($v);
            $content .= "$k=$v_str,";
        }

        $content = trim($content, ',');
        $sql .= $content;
        $sql .= " WHERE $condition";
        $result = self::Query($sql);

        if (false == $result) {
            self::Close();
            return false;
        }

        return true;
    }

    /**
     * 更新表内容 与update一样，不同的是此处返回执行语句受影响的条数
     * @param string $table 表名
     * @param array $condition 要删除的条件数组
     * @param array|string $id  array 则组合condition |string 则 $pkname 对应的值组合sql结果 id=1
     * @param array  $updaterow 更新的字段数组
     * @param string $pkname  前置 $id 为字符串时 ，此为更新的字段名
     * @return integer 如果返回-1 表示更新失败
     */
    static public function UpdateToCount($table = null, $id = 1, $updaterow = array(), $pkname = 'id') {

        if (null == $table || empty($updaterow) || null == $id) {
            return false;
        }

        if (is_array($id)) {
            $condition = self::BuildCondition($id);
        } else {
            $condition = "$pkname='" . self::EscapeString($id) . "'";
        }

        static::Instance();
        $sql = "UPDATE ".static::$mDefaultSchema."{$table} SET ";

        $content = null;

        foreach ($updaterow as $k => $v) {
            $v_str = self::CovertStr($v);
            $content .= "$k=$v_str,";
        }

        $content = trim($content, ',');
        $sql .= $content;
        $sql .= " WHERE $condition";
        $result = self::Query($sql);

        if (false == $result) {
            self::Close();
            return -1;
        }

        return pg_affected_rows($result);
    }

    /**
     * 获取字表字段信息 ,表名如果不在public.下。需要写全表名
     *
     * @param string $table
     * @return array
     */
    static public function GetTableField($table) {
        static::Instance();
        return pg_meta_data(static::$mConnection, $table);
    }

    /**
     * 获取字表字段信息 建议直接使用 GetTableField
     * @param string $table
     * @param array $select_map  enum|set 返回支持的key
     * @return array
     */
    static public function GetField($table, $select_map = array()) {
        $fields = array();
        $q = self::GetTableField($table);
        foreach ($q as $Field => $r) {

            $Type = $r ['Type'];

            $type = 'varchar';
            $cate = 'other';
            $extra = null;

            if (preg_match('/^id$/i', $Field)) {
                $cate = 'id';
            } else if (preg_match('/^_time/i', $Field)) {
                $cate = 'integer';
            } else if (preg_match('/^_number/i', $Field)) {
                $cate = 'integer';
            } else if (preg_match('/_id$/i', $Field)) {
                $cate = 'fkey';
            }
            if (preg_match('/text/i', $Type)) {
                $type = 'text';
                $cate = 'text';
            }
            if (preg_match('/date/i', $Type)) {
                $type = 'date';
                $cate = 'time';
            } else if (preg_match('/int/i', $Type)) {
                $type = 'int';
            } else if (preg_match('/(enum|set)\((.+)\)/i', $Type, $matches)) {
                $type = strtolower($matches [1]);
                eval("\$extra=array($matches[2]);");
                $extra = array_combine($extra, $extra);

                foreach ($extra as $k => $v) {
                    $extra [$k] = isset($select_map [$k]) ? $select_map [$k] : $v;
                }
                $cate = 'select';
            }

            $fields [] = array('name' => $Field, 'type' => $type, 'extra' => $extra, 'cate' => $cate);
        }
        return $fields;
    }

    /**
     * 判断表数据指定条件id是否存在
     * 如果不使用返回id ，建议使用 $condition['select'] ='count(1)' 此时返回的是记录总数，或直接使用 $condition['select'] ='id'
     * @param string $table 表名
     * @param array $condition 查询条件数组
     * @param string $select  默认*  例: id
     * @param boolean $no_public 表名是否自动加public.
     * @return boolean
     */
    static public function Exist($table, $condition = array(), $select = "*", $no_public = false) {
        $row = self::LimitQuery($table, array('condition' => $condition, 'one' => true, 'select' => $select), $no_public);
        return empty($row) ? false : (isset($row ['id']) ? $row ['id'] : (isset($row ['count']) ? $row ['count'] : true));
    }

    static public function BuildCondition($condition = array(), $logic = 'AND', $protect = true) {
        if (is_string($condition) || is_null($condition)) {
            return $condition;
        }

        $logic = strtoupper($logic);
        $content = null;
        foreach ($condition as $k => $v) {
            $v_str = null;
            $v_connect = '=';

            if (is_numeric($k)) {
                $content .= $logic . ' (' . self::BuildCondition($v, $logic) . ')';
                continue;
            }

            $maybe_logic = strtoupper($k);
            if (in_array($maybe_logic, array('AND', 'OR'))) {
                $content .= $logic . ' (' . self::BuildCondition($v, $maybe_logic) . ')';
                continue;
            }

            if (is_numeric($v)) {
                $v_str = "'{$v}'";
            } else if (is_null($v)) {
                $v_connect = ' IS ';
                $v_str = ' NULL';
            } else if (is_array($v)) {
                if (isset($v [0])) {
                    $v_str = null;
                    foreach ($v as $one) {
                        if (is_numeric($one)) {
                            $v_str .= ',' . "'$one'";
                        } else {
                            $v_str .= ',\'' . self::EscapeString($one) . '\'';
                        }
                    }
                    $v_str = '(' . trim($v_str, ',') . ')';
                    $v_connect = 'IN';
                } else if (empty($v)) {
                    $v_str = $k;
                    $v_connect = '<>';
                } else {
                    $v_connect = array_shift(array_keys($v));
                    $v_s = array_shift(array_values($v));
                    $v_str = "'" . self::EscapeString($v_s) . "'";
                    $v_str = is_numeric($v_s) ? "'{$v_s}'" : $v_str;
                }
            } else {
                $v_str = "'" . self::EscapeString($v) . "'";
            }

            $content .= $protect ? " $logic $k $v_connect $v_str " : " $logic $k $v_connect $v_str ";
        }

        $content = preg_replace('/^\s*' . $logic . '\s*/', '', $content);
        $content = preg_replace('/\s*' . $logic . '\s*$/', '', $content);
        $content = trim($content);

        return $content;
    }

    static public function CheckInt($id) {
        $id = intval($id);

        if (0 >= $id) {
            throw new Exception('must int!');
        }

        return $id;
    }
    /**
     * 事务开始
     */
    static public function beginTransaction() {
        return self::TransBegin();
    }

    /**
     * 事务提交
     */
    static public function commitTransaction() {
        return self::TransCommit();
    }

    /**
     * 事务回滚
     */
    static public function rollbackTransaction() {
        return self::TransRollback();
    }

    /**
     * 获取表字段
     * @param type $schema
     * @param type $table
     * @return type
     */
    public static function get_column_arr($schema, $table) {
        $sql = "SELECT column_name FROM information_schema.columns WHERE table_schema = '{$schema}' and table_name='{$table}'";
        $result = self::GetQueryResult($sql, false);

        $columns = array();
        if ($result) {
            $columns = Utility::GetColumn($result, 'column_name');
        }

        return $columns;
    }
}
