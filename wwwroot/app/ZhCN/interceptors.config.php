<?php

if (!defined('IN_PX'))
    exit;
/**
 * 后台访问的路由配置
 * 本地缓存
 */
return array(
    //定义拦截器
    'interceptor' => array(
        'setPageTitle' => 'App\ZhCN\Interceptor\SetPageTitle'
    ),
    //拦截器堆栈
    'stack' => array(
        'default' => array(
            'setPageTitle' => array(
                'includeRoute' => array(
                    '/**'
                )
            )
        )
    ),
    //全局拦截器
    'ref' => 'default',
    'returnParamKey' => 'url', //返回url的关键字，如：url=... 或者 returnUrl=...
    'redirect' => array(
        '/' => 'login',
        'controllerIndex' => '
        /login'
    ),
    'anchor' => ''
);
