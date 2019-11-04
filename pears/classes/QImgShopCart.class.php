<?php

/**
 * 图片购物车
 */

class QImgShopCart
{
    /**
     * 根据id获取购物车信息
     * @param $id
     * @return array
     */
    public static function getById($id){
        $sql = "SELECT * FROM PUBLIC.shop_cart WHERE id = '{$id}'";
        return DB::GetQueryResult($sql,true);
    }

    /**
     * 根据id获取购物车信息
     * @param $id
     * @return array
     */
    public static function getByEid($eid){
        $sql = "SELECT * FROM PUBLIC.shop_cart WHERE eid = '{$eid}'";
        return DB::GetQueryResult($sql,true);
    }

    /**
     * 根据图片id 获取购物车信息
     * @param $username
     * @param $img_id
     * @return array
     */
    public static function getByImgId($username,$img_id){
        $sql = "SELECT * FROM PUBLIC.shop_cart WHERE img_id = '{$img_id}' and username = '{$username}' ";
        return DB::GetQueryResult($sql,true);
    }

    /**
     * 获取图片id 是否存在于购物车中
     * @param string $username
     * @param array $img_ids
     * @return array
     */
    public static function getIsAddShop(string $username,array $img_ids){
        $ids_sql = implode(",",$img_ids);
        if(empty($ids_sql)){
            return array();
        }
        $sql = "SELECT count(1),img_id FROM PUBLIC.shop_cart WHERE username = '{$username}' and img_id in({$ids_sql}) AND is_del = 'f' group by img_id";

        $list = DB::GetQueryResult($sql,false);
        $in_id = array_column($list,"img_id");

        $return = array();
        foreach ($img_ids as $v){
            $return[$v] = in_array($v,$in_id);
        }
        return $return;
    }

    /**
     * 获取某大分类下某用户的购物车列表
     * @param $params   username| domain_id | big_type |array eids  is_del | t,f,all
     * @return array
     */
    public static function getList($params){

        $sql_where = "";
        if($params['eids']){
            $sql_where = " AND sc.eid in(".implode(",",$params['eids']).") ";
        }
        if($params['big_type']){
            $sql_where .= " AND i.big_type = '{$params['big_type']}'";
        }
        if($params['is_del'] =='all'){
            $sql_where .= " AND sc.is_del in ('t','f')";
        }else{
            $sql_where .= " AND sc.is_del = 'f'";
        }

        $sql = "SELECT sc.eid as sc_id,i.* from  
                PUBLIC.shop_cart as sc
                JOIN PUBLIC.img as i on sc.img_id = i.id  
                WHERE sc.username = '{$params['username']}'
                     {$sql_where}
                    AND i.domain_id = '{$params['domain_id']}'
                    AND i.audit_state = '2'
                    AND i.is_del ='f'
              ORDER BY sc.update_time DESC
              ";
        return DB::GetQueryResult($sql,false);
    }

    /**
     * 获取购物车列表的总数
     * @param $params
     * @return array
     */
    public static function getListCount($params){
        $sql = "SELECT i.big_type,count(1)  
                FROM PUBLIC.shop_cart as sc
                JOIN PUBLIC.img as i on sc.img_id = i.id  
                WHERE sc.username = '{$params['username']}'
                    AND sc.is_del = 'f'
                    AND i.domain_id = '{$params['domain_id']}'
                    AND i.audit_state = '2'
                    AND i.is_del ='f' 
                GROUP BY i.big_type
              ";
        return DB::GetQueryResult($sql,false);
    }

    /**
     * 获取购物车总数
     * @param $params
     * @return array
     */
    public static function getCartCount($params){
        $sql = "SELECT count(1)  
                FROM PUBLIC.shop_cart as sc
                JOIN PUBLIC.img as i on sc.img_id = i.id  
                WHERE sc.username = '{$params['username']}'
                    AND sc.is_del = 'f'
                    AND i.domain_id = '{$params['domain_id']}'
                    AND i.audit_state = '2'
                    AND i.is_del ='f' 
              ";
        return DB::GetQueryResult($sql,true);
    }

    /**
     * 添加购物车
     * @param $username
     * @param $img_id
     * @return bool|resource
     */
    public static function add(string $username,int $img_id){

        DB::TransBegin();
        $sql = "INSERT INTO  PUBLIC.shop_cart
            (username,img_id) VALUES('{$username}','{$img_id}')
            ON CONFLICT (username,img_id) 
             DO UPDATE SET is_del='f',update_time=current_timestamp
             RETURNING id
             ";
        $rs = DB::Query($sql);
        if(!$rs){
            DB::TransRollback();
            return false;
        }
        $row = pg_fetch_assoc($rs);
        if(!$row['id']){
            DB::TransRollback();
            return false;
        }
        $IDEncipher = new IDEncipher();
        $eid = $IDEncipher->encrypt($row['id']);
        $updata = array();
        $updata["eid"] = $eid;
        $rs = DB::Update("public.shop_cart",$row['id'],$updata,"id");
        if(!$rs){
            DB::TransRollback();
        }else{
            DB::TransCommit();
        }
        return $rs;
    }

    /**
     * 删除用户的购物车
     * @param $username
     * @param array $ids
     * @return bool|resource
     */
    public static function del(string $username,array $eids){

        if(!is_array($eids)){
            $eids = array($eids);
        }
        $eids_sql = implode(",",$eids);

        $sql = "UPDATE PUBLIC.shop_cart set is_del = 't', update_time = current_timestamp where username = '{$username}' and eid in({$eids_sql})";
        return DB::Query($sql);
    }
}