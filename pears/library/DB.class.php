<?php


class DB extends QDB{
    public static $mInstance = null;   //子类需要声明
    public static $mConnection = null; //子类需要声明
    public static $mDebug = false;  //子类需要声明
    public static $mError = null;   //子类需要声明
    public static $mCount = 0;      //子类需要声明
    public static $mResult;         //子类需要声明
    public static $dbConfig = 'db'; //使用的数据库配置
    public static $mDefaultSchema = ''; //for insert update delete  GetDbRowById LimitQuery UpdateToCount 一些保留字的表查询会有问题 如user表。 需要public.user
    public static $mSearchPath = 'public';//设置默认的schema搜索路径
    
}