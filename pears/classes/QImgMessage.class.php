<?php

/**
 * 内部消息
 * Class QImgMessage
 */

class QImgMessage{
    /**
     * 添加消息通知
     * @param string $username
     * @param array $message   content是消息内容，type是消息分类，以后可随意扩展
     */
    private static function add(string $username,array $message){
        $insert = array();
        $insert['username'] = $username;
        $insert['message'] = json_encode($message,JSON_UNESCAPED_UNICODE);
        $insert['is_read'] = 'f';

        return DB::Insert("public.message",$insert,"id");
    }

    /**
     * 添加索材审核通知
     * @param string $username
     * @param string $message
     * @return bool
     */
    public static  function addAuditMessage(string $username,string $message,int $audit_state){
        $params = array();
        $params['type'] = "audit";
        $params['title'] = "素材审核通知";
        $params['audit_state'] =$audit_state;
        $params['content'] = $message;
        return static ::add($username,$params);
    }
    
    /**
     * 精选推荐
     * @param string $username
     * @param string $message
     * @return bool
     */
    public static  function addRecommendMessage(string $username,string $message,int $audit_state){
        $params = array();
        $params['type'] = "recommend";
        $params['title'] = "精选推荐上榜啦！";
        $params['audit_state'] =$audit_state;
        $params['content'] = $message;
        return static ::add($username,$params);
    }
    /**
     * 添加索材审核通知
     * @param string $username
     * @param string $message
     * @return bool
     */
    public static  function unRecommendMessage(string $username,string $message,int $audit_state){
        $params = array();
        $params['type'] = "unrecommend";
        $params['title'] = "精选推荐取消";
        $params['audit_state'] =$audit_state;
        $params['content'] = $message;
        return static ::add($username,$params);
    }


    
    /**
     * 添加每日积分变动通知
     * @param string $username
     * @param string $message
     * @return bool
     */
    public static  function addPointsMessage(string $username,string $message,int $audit_state){
        $params = array();
        $params['type'] = "point";
        $params['title'] = "积分变动通知";
        $params['audit_state'] =$audit_state;
        $params['content'] = $message;
        return static ::add($username,$params);
    }

    /**
     * 交互消息 （有互动消息时每天上午10点发送）
     * 添加关注消息
     * @param string $username
     * @param string $message
     * @return bool
     */
    public static  function addPraiseMessage(string $username,string $message){
        $params = array();
        $params['type'] = "praise";
        $params['title'] = "恭喜，您贡献的素材被关注啦！";
        $params['content'] = $message;
        return static ::add($username,$params);
    }

    /** 获取列表
     * @param string $username 用户名
     * @param string $is_read 是否忆读 t |f
     * @param int $limit      分页数
     * @param int $offset     每页显示数
     * @return array
     */
    public static function list(string $username,string $is_read,int $limit,int $offset){

        $sql = "SELECT id,message->>'type' as type,message->>'title' as title, message->>'content' as content,message->>'type' as type,message->>'audit_state' as audit_state ,create_time
                FROM PUBLIC.message  
                WHERE username = '{$username}'
                      AND is_read ='{$is_read}'
              ORDER BY id DESC
              LIMIT {$limit}
              OFFSET {$offset}
              ";
        return DB::GetQueryResult($sql,false);
    }

    /** 获取总数
     * @param string $username 用户名
     * @param string $is_read 是否已读 t|f
     * @return mixed
     */
    public static function count(string $username,string $is_read){

        $sql = "SELECT count(1)
               FROM  PUBLIC.message  
                WHERE username = '{$username}'
                    AND is_read ='{$is_read}'
              ";
       $result= DB::GetQueryResult($sql,true);
       return isset($result['count'])?$result['count']:0;
    }

    /**
     * 设置已读
     * @param int $id  id
     * @param string $username 用户名
     * @return bool
     */
    public static function read(int $id,string $username){
        $update = array();
        $update['is_read'] = 't';

        $cond = array();
        $cond['id'] = $id;
        $cond['username'] = $username;
        return DB::Update("PUBLIC.message",$cond,$update);
    }
}