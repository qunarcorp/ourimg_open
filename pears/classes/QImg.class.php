<?php

/**
 * Desc: 本类主要实现关于public.img表相关操作
 */

class QImg {
    //更新img表
    public static function update($up_where, $up_data){
        return DB::update('public.img', $up_where, $up_data);
    }

    //获取img信息
    public static function getInfo($cond){
        $img_info = DB::GetTableRow('public.img', $cond);

        if ($img_info) {
            $img_info["small_type"] = json_decode($img_info["small_type"], true);
        }

        return $img_info;
    }

    /**
     * 获取图片原始地址
     * @param $img
     * @param int $system_domain
     * @param string $in
     * @return string
     */
    public static function getImgUrl($img,$system_domain=0,$in="inner_domain"){
        return Storage::getObjectFullPath($img,$in);
    }


    /**
     * 获取图片缩略图
     * @param $img
     * @param $width
     * @param $height
     * @param int $system_domain
     * @param string $in 内网还是外网  inner_domain|out_domain
     * @return string
     */
    public static function getImgUrlResize($params){
        global $INI;

        if($params['width'] < $params['height']&&$params['r_height']){
            //横坚比 取最大的一边缩放值
            $params['r_width'] = round($params['width'] * ($params['r_height']/$params['height']));
        }
        if(!$params['r_height']){
            if($params['r_width']>=$params['width']){
                $params['r_width'] = $params['width'];
                $params['r_height'] = $params['height'];
            }else{
                $params['r_height'] = round($params['height'] * ($params['r_width']/$params['width']));
            }
        }

        return Storage::generateImgThumbnail($params['img'],$params['r_width'],$params['r_height']);
    }
    
    /**
     * 获取图片缩略图
     * @param $img
     * @param $width
     * @param $height
     * @param int $system_domain
     * @param string $in 内网还是外网  inner_domain|out_domain
     * @return string
     * 只限制高500
     */
    public static function getImgUrlResizeHeight($params){
        global $INI;
        
        //固定高度 $params['r_height']
        //计算宽度
        $params['r_width'] = round($params['width'] * ($params['r_height']/$params['height']));

        return Storage::generateImgThumbnail($params['img'],$params['r_width'],$params['r_height']);
    }

    /**
     * only edit purpose
     * @param $eid
     * @return array
     */
    public static function onlyEditPurpose($eid)
    {
        $eid = (array) $eid;
        if (empty($eid)) {
            return [];
        }
        $eidStr = array2insql($eid);

        $sql = <<<SQL
    select eid, title, purpose 
        from public.img 
        where is_del = 'f' and audit_state = 2 and purpose = 3 and eid in ({$eidStr}) 
SQL;
        $onlyEditPurposeList = (array) DBSLAVE::GetQueryResult($sql, false);

        return $onlyEditPurposeList;
    }
}
