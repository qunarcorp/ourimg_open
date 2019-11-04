<?php

/**
 * 图片下载
 */

class QImgDownload
{
    protected $systemDomain;

    protected $loginUserName;

    protected $imgIdsStr;

    protected $eIdsStr;

    protected $removePath;

    const DOWNLOAD_ROOT_PATH = "";

    public function __construct()
    {
        global $system_domain, $login_user_name ,$INI;
        $this->systemDomain = $system_domain;
        $this->loginUserName = $login_user_name;
        $this->DOWNLOAD_ROOT_PATH = $INI['stats']['download_root_path'];
    }

    /**
     * get download url
     * @param $imgCollect
     * @param string $in
     * @return string
     * @throws QImgApiException
     */
    public function download($imgCollect, $in = "inner_domain")
    {
        if (empty($imgCollect)) {
            throw new QImgApiException("无下载的资源");
        }

        $imgIdsStr = $this->getDownloadImgMark($imgCollect);
        $filePackageCache = $this->getDownloadCachePackage($imgIdsStr, $this->loginUserName);
        if ($filePackageCache) {
            $downloadId = $filePackageCache["username"] == $this->loginUserName
                ? $filePackageCache["id"]
                : DB::Insert("public.download", [
                    "img_ids" => $this->imgIdsStr,
                    "username" => $this->loginUserName,
                ], "id");
            $s3Path = $this->s3FileMove($filePackageCache["filename"], $this->getTarName($filePackageCache["filename"], $this->loginUserName, (new IDEncipher)->encrypt($downloadId)));
        }else{
            $downloadId = DB::Insert("public.download", [
                "img_ids" => $this->imgIdsStr,
                "username" => $this->loginUserName,
            ], "id");
            $downloadPath = $this->downloadSource($imgCollect);
            $tarFilePath = $this->packaging($downloadPath, (new IDEncipher)->encrypt($downloadId));
            $s3Path = $this->uploadToS3($tarFilePath);
            $this->log("info", "eid:{$this->eIdsStr} 打包成功,s3 path:{$s3Path}");
        }
        QImgDownload::updateDownloadUrl($downloadId, $s3Path);//更新下载链接
        foreach($imgCollect as $imgInfo) {
            QImgDownload::addDownloadHistory($this->loginUserName, $imgInfo['id']);
            QImgOperate::addDownloadNum($imgInfo['id']);
            
            //计算积分
            $params_point = [
                'username' => $imgInfo['username'],//积分用户
                'operate_username' => $this->loginUserName,//操作用户
                'img_id' => $imgInfo['id'],//操作图片id
                'operate_type' => 'download',//操作图片类型
            ];
            $task_rs = QImgPoints::pointsDeal($params_point);
        }
        $this->clearDownloadFile();
        
        return QImg::getImgUrl($s3Path, $this->systemDomain, $in);
    }

    /**
     * clear download file
     */
    public function clearDownloadFile()
    {
        foreach ($this->removePath as $filePath) {
            if (is_dir($filePath)) {
                @rmdir($filePath);
            }else{
                @unlink($filePath);
            }
        }
    }

    /**
     * get tar name
     * @param $oldFilePath
     * @param null $newUsername
     * @param null $newDownloadId
     * @return mixed
     */
    private function getTarName($oldFilePath, $newUsername = null, $newDownloadId = null)
    {
        $fileName = basename($oldFilePath);

        list($username, $downloadId, $imgCount) = explode("_", trim($fileName, '.tar'));

        $newFileName = sprintf(
            "%s_%s_%s_%s.tar",
            $newUsername ? $newUsername : $username,
            $newDownloadId ? $newDownloadId : $downloadId,
            $imgCount ? $imgCount : count(explode(",", $this->eIdsStr)),
            date("Ymd")
        );

        return str_replace($fileName, $newFileName, $oldFilePath);
    }

    /**
     * s3 file move
     * @param $filePackagePath
     * @param $newFilePackagePath
     * @return mixed
     * @throws QImgApiException
     */
    private function s3FileMove($filePackagePath, $newFilePackagePath)
    {
        if ($filePackagePath != $newFilePackagePath) {
            if (! Storage::copy($filePackagePath, $newFilePackagePath)) {
                $this->log("warning", "s3资源复制失败，原链接:$filePackagePath,新链接:$newFilePackagePath");
                throw new QImgApiException("下载失败");
            }
            $this->log("info", "eid:{$this->eIdsStr} copy成功,s3 old path:{$filePackagePath} ;s3 new path:{$newFilePackagePath}");
            if (substr($filePackagePath, 0, (int) strpos($filePackagePath, "_"))
                == substr($newFilePackagePath, 0, (int) strpos($newFilePackagePath, "_"))) {
                $this->removeS3File($filePackagePath);
            }
        }

        return $newFilePackagePath;
    }

    /**
     * remove s3 file
     * @param $filePackagePath
     */
    private function removeS3File($filePackagePath)
    {
        return Storage::del($filePackagePath,10);
    }

    /**
     * 上传到s3
     * @param $filePath
     * @return mixed
     * @throws QImgApiException
     */
    private function uploadToS3($filePath)
    {
        $uploadResult = Storage::put($filePath, "download", "");

        if(empty($uploadResult['key'])){
            $this->log("warning", "eid:{$this->eIdsStr};打包后上传失败");
            throw new QImgApiException("上传到存储失败");
        }

        return $uploadResult['key'];
    }

    /**
     * packaging
     * @param $path
     * @param $downloadId
     * @return string
     * @throws QImgApiException
     */
    private function packaging($path, $downloadId)
    {
        $tarFileName = $this->getTarName("{$this->loginUserName}_{$downloadId}.tar");
        $tarFilePath = dirname($path)."/".$tarFileName;
        array_push($this->removePath, $tarFilePath, $path);
        chdir($path);
        $shellCmd = sprintf("tar -cf %s *", $tarFilePath, dirname($path));
        $this->log("info", "eid:{$this->eIdsStr};shell:{$shellCmd}");
        $uploadError = exec($shellCmd, $output, $returnVar);
        if(0 != $returnVar){
            $this->log("warning", "eid:{$this->eIdsStr};生成压缩包失败;error:{$uploadError}");
            throw new QImgApiException("打包失败");
        }
        return $tarFilePath;
    }

    /**
     * @param $shopCartCollect
     * @return mixed
     * @throws QImgApiException
     */
    private function downloadSource($shopCartCollect)
    {
        $downloadPath = $this->makeDownloadPath($this->DOWNLOAD_ROOT_PATH.$this->loginUserName."/".md5($this->imgIdsStr));
        $filePathList = [];
        foreach($shopCartCollect as $img){
            //文件名以 eid +扩展 做文件名
            $fileName =  ($img['title'] ? ($img['title'] . "_") : '') . $img['eid'].".".$img['ext'];
            //下载文件
            $filePath = $downloadPath."/".$fileName;
            $downloadResult = Storage::get($img['url'], $filePath);
            array_push($filePathList, $filePath);
            //下载失败处理
            if(! $downloadResult || ! file_exists($downloadPath."/".$fileName)){
                $this->log("warning", "eid:{$this->eIdsStr};从ceph下载文件失败;{$img['url']}");
                throw new QImgApiException("打包失败");
            }
        }
        $this->removePath = $filePathList;
        return $downloadPath;
    }

    /**
     * make download path
     * @param $path
     * @return mixed
     * @throws QImgApiException
     */
    private function makeDownloadPath($path)
    {
        //建下载目录
        @mkdir($path,0777,true);

        //判断是否目录可写。不可写需要返回
        if(!file_exists($path)){
            $this->log("warning", "建临时目录{$path}失败");
            throw new \QImgApiException("打包失败");
        }

        return $path;
    }


    /**
     * 获取有效历史下载缓存
     * @param $imgIdsStr
     * @param $username
     * @return array|int|null
     * @throws QImgApiException
     */
    private function getDownloadCachePackage($imgIdsStr, $username)
    {
        $expreDate = date("Y-m-d H:i:s", strtotime("2 hour"));
        $sql = <<<SQL
 SELECT id, username, filename, file_expire_date FROM PUBLIC.download 
 WHERE img_ids = '{$imgIdsStr}' and file_expire_date >= '$expreDate'
 order by case when username = '$username' then 1 else 2 end asc, id desc limit 1 
 for update
SQL;
        $downLoadRecordResult = DB::Query($sql);

        if ($downLoadRecordResult === false){
            throw new QImgApiException("下载异常");
        }
        $downLoadRecord = array_change_key_case(pg_fetch_assoc($downLoadRecordResult), CASE_LOWER);
        pg_free_result($downLoadRecordResult);

        if (! $downLoadRecord) {
            return null;
        }

        return (strtotime($downLoadRecord["file_expire_date"]) > strtotime("2 hour"))
            ? $downLoadRecord
            : null;
    }

    /**
     * 获取下载图片标识
     * @param $shopCartCollect
     * @return string
     */
    private function getDownloadImgMark($shopCartCollect)
    {
        $shopCartCollect = list_sort($shopCartCollect, "id");
        $imgIds = array_column($shopCartCollect, "id");
        $eIds = array_column($shopCartCollect, "eid");
        $this->imgIdsStr = implode(",", $imgIds);
        $this->eIdsStr = implode(",", $eIds);

        return $this->imgIdsStr;
    }

    /**
     * 获取图片是否存在已下载。存在则不会在次打包
     * @param $username
     * @param $img_ids 以逗号分隔的字符串
     * @return array
     */
    public static function getExists($username,$img_ids_str){
        $expire_date = date("Y-m-d H:i:s",strtotime("-2 hour"));

        $sql = "SELECT filename FROM  PUBLIC.download WHERE username='{$username}' and img_ids = '{$img_ids_str}' and file_expire_date>'{$expire_date}' order by id desc limit 1  ";
        return $exists = DB::GetQueryResult($sql,true);
    }

    /**
     * 添加到下载目录
     * @param $username
     * @param $img_ids_str
     * @return bool
     */
    public static function add($username,$img_ids_str){
        $insert = array();
        $insert["img_ids"] = $img_ids_str;
        $insert["username"] = $username;
        $download_id = DB::Insert("public.download",$insert,"id");
        return $download_id;
    }

    /**
     *  更新下载链接地址
     * @param $download_id
     * @param $url
     * @return bool
     */
    public static function updateDownloadUrl($download_id,$url){
        $update = array();
        $update["filename"] = $url;
        $update["file_expire_date"] = date("Y-m-d H:i:s",strtotime("+2 day")); //下载文件2天内有效， 删除程序需要将这个打标签
        return DB::Update("public.download",$download_id,$update,'id');
    }

    /**
     * 上传的临时文件，图太小不合归的不能立马删除 ，需要定时删除， 使用download处理
     * @param $url
     * @param int $expire_time
     * @return bool
     */
    public static function smallImgByDownLoadDel($url,$expire_time=300){
        $update = array();
        $update["username"] = "";
        $update["filename"] = $url;
        $update["file_expire_date"] = date("Y-m-d H:i:s",time()+$expire_time); //下载文件2天内有效， 删除程序需要将这个打标签
        return DB::Insert("public.download",$update,'id');
    }

    /**
     * 添加下载历史 ,同一下载链接只留一份
     * @param $username 用户名
     * @param $img_id 图片的未加密id
     */
    public static function addDownloadHistory($username,$img_id){
        $sql = "INSERT INTO  PUBLIC.download_history
            (username,img_id) VALUES('{$username}','{$img_id}')
            ON CONFLICT (username,img_id) 
             DO UPDATE SET update_time=current_timestamp
             ";
         return DB::Query($sql);
    }

    /**
     * download log
     * @param $level
     * @param $message
     */
    private function log($level, $message)
    {
        QLog::{$level}('shop_cart', 'download', $message);
    }

}