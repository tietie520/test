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
        'login' => 'App\Admin\Interceptor\Login',
        'security' => 'App\Admin\Interceptor\Security',
        'createConfig' => 'App\Admin\Interceptor\CreateConfig'
    ),
    //拦截器堆栈
    'stack' => array(
        'default' => array(
            //'redirect' => 'system/login' 私有拦截器的转向
            'createConfig' => array(
                'includeRoute' => array(
                    '/**'
                )
            ),
            'login' => array(
                'excludeRoute' => array(
                    'admin/system/login', 'handler/Admin.System.Login', 'handler/Admin.System.Upload'
                )
            ),
            'security' => array(
                'excludeRoute' => array(
                    'admin/system/login', 'admin/system/welcome', 'admin/system/password',
                    'admin/system/statistics',
                    'handler/Admin.System.Login',
                    'handler/Admin.System.EditPwd',
                    'handler/Admin.AreaOrShopId'
                )
            )
        )
    ),
    //全局拦截器
    'ref' => 'default',
    'returnParamKey' => 'url', //返回url的关键字，如：url=...，returnUrl=...
    'redirect' => 'system/login'
);
