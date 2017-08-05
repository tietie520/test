<?php

if (!defined('IN_PX'))
    exit;
/**
 * 返回数据库访问
 * 本地缓存
 */
return array(
    'default' => array(
        'charset' => 'utf8',
        'prefix' => 'px_',
        
        'driver' => 'mysql',
        'persistent' => false,//使用长连接
        'host' => '116.62.161.204',
        'port' => 3306,
        'dbName' => 'sanyuan',
        'user' => 'root',
        'password' => 'root',


        'useSequencer' => false,//是否使用全局序列
        'sequencer' => '__SEQ__',//全局序列占位符
        'level2cache' => true,
        'level2TopLimit' => 0,
        'sessionType' => 'database', //database or memcache,memcached,redis
        'cacheType' => 'file', //file or memcache,memcached,redis
        'memServers' => array(
//            'aliocs' => array('***.m.cnhzaliqshpub001.ocs.aliyuncs.com', 11211, '***', '***')
//            'memcache' => array('127.0.0.1', 11211)
            'memcached' => array(
                array('127.0.0.1', 11211, 1)
            )
        )
    )
);
