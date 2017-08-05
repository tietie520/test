<?php

if (!defined('IN_PX'))
    exit;
/**
 * 版本信息 history
 * v3.2.4 dq版本升级到3.3.1 (Service.Templates.php UrlHelper.php更改) update:2013-10-10
 * v3.2.5 sql访问器重写，支持链式操作，dq版本升至3.3.2，sql访问器全部使用链式操作 update:2013-10-10
 * v3.2.6 Controller -> 3.2.3 \Phoenix\ProxyFactory -> 3.2.2 update:2013-10-16
 * v3.2.7 Controller -> 3.2.4 \Phoenix\CacheFile -> gc 修正 \Tools\File -> 修正 \Phoenix\Session 修正(__set方法) update:2013-12-08
 * v3.2.8 Controller -> 3.2.5 _setViewOrRedirect -> 增加forward \Phoenix\AbstractInterceptor -> 3.2.3 增加了一个拦截器匹配标识  Phoenix -> 1.0.7 结构调整 update:2013-12-28
 * v3.2.9 Controller -> 3.2.7 \Phoenix\AbstractInterceptor -> 3.2.4 HttpHandler -> 3.2.2 修正了forward的拦截器共享及保留最初入口的url分析 update:2014-02-22
 */
return array(
    'db' => '2.2.0',
    'js' => '2.1.0',
    'kindeditor' => '4.1.7'
);
