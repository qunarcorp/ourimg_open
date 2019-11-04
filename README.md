#图片管理系统

## 说明
```
主界面：素材资源的展示
版权授权：相关授权协议的签订、著作权授权的声明
用户素材管理部分：支持用户版权图片/视频等素材的展示/上传/编辑/下载/收藏/点赞
管理员中心部分：支持素材的审核，系统权限的分配，征集活动任务的发布管理，积分商城的资源管理
积分商城：支持用户贡献素材积分的获取，查看，商品兑换
数据报表：用户贡献素材/交互等数据排行版、多维度数据的统计管理
```
# 安装说明
## 系统需要php composer支持
### https://docs.phpcomposer.com/00-intro.html 
### 在根目录执行 
###  composer install --no-plugins --no-scripts
### 如果是发布系统发布job 需要排除目录 /vendor

## 系统配置

### 登录系统配置
```
支持ldap 本地校验需自行实行
conf/configure/auth.php
```
### 数据库配置
```
仅支持postgresql数据库
conf/configure/db_config.php
```
### 建表
```
db.sql 导入库中即可
```

### 系统邮件配置
```
conf/configure/mail.php
```
### 系统通知配置
```
支持邮件以及qtalk通知，qtalk需要自行适配
conf/configure/notify.php
```
### 存储配置
```
支持s3 百度 以及本地存储
conf/configure/storage.php
```

