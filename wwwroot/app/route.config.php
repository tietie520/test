<?php

if (!defined('IN_PX'))
    exit;
/**
 * 路由配置
 * 本地缓存
 */
return array(
    'root' => '/',
    'namespace' => 'App\\',
    'charset' => 'utf-8', //__Produces 返回时调用
    'suffix' => array('php', 'json', 'xml'), //支持的访问url后缀，可访问的url后缀为空或指定的后缀
    'ejector' => array('.hot-update.json'), //debug模式下，在此排除器中文件框架直接返回404
    'taglib' => '.php', //模板文件标签域(即模板标签文件后缀，以便于正确使用模板标签)
    'domain' => '', //cookie使用的域
    'caseSensitive' => false, //路由是否大小写敏感
    'viewConstant' => array(//语言版本下级目录路由视图恒定 zh-CN/admin en/admin...
        //视图恒定，始终使用恒定语言版本下的视图文件，在语言版本变化时，视图使用的目录恒定，不跟随语言版本变化而变化
        //key小写
        //可随意更改的路由  =>  映射的目录名，拦截器中或文件中使用__VC__及映射后的目录，可以免去改动配置的操作
        //如后台原来为admin，改成nimda后直接浏览器使用而不需要再去文件中将所有的admin改成nimda
        //注意拦截器的路径可能需要修改
        'admin' => 'admin'
    ),
    //若配置，静态资源路径以及上传文件则使用远程目录，不要以 "/" 结尾
    //映射为 __CDN__
    'cdnDomain' => '',
    'rpcTriggerAlias' => 'rpc',
    'handlerDirectory' => 'Handler',
    'restXmlRootTag' => 'data',
    'assetsDirectory' => 'assets', //视图文件目录
    'staticDirectory' => 'static', //静态资源文件目录 常用 static,publish
    'uploadDirectory' => 'uploads', //用来显示存放图片的路径，远程显示将忽略此目录
    /**
     * 语言版本的视图文件锁定为单一目录，为空视图目录则与语言对应，同时controller也为对应的单一目录
     * 视图启用 i18n 时配置，否则置为空
     */
    'languagesViewSingleDirectory' => '',
    'languages' => array(
        //第一项为默认语言，key为语言版本，id语言版本id ,shared 语言版本目录
        'zh-CN' => array(
            'id' => 0,
            'shared' => 'zh-CN'
        ),
        'en' => array(
            'id' => 1,
            'shared' => 'zh-CN', //路由
            'vue' => 'zh-CN' //目录
        ),
        'es' => array(
            'id' => 2,
            'shared' => 'zh-CN',
        ),

    ),
//    , 'i18nPath' => 'i18n' //国际化文件目录，走拦截器更灵活
    'bundles' => array(
        'cfg' => 'data/config.cache.php',
        'dsn' => 'data/dsn.cache.php',
        'setting' => 'data/setting.cache.php'
    ),
    /**
     * 框架路由注入缓存
     * 一般情况下不需要更改
     * 可指定内存缓存，内存缓存和 dsn.cache.php `default` => `cacheType` 有依赖
     * 'Phoenix\Cache\Framework\Impl\MemcacheStoreImpl'
     * 'Phoenix\Cache\Framework\Impl\MemcachedStoreImpl'
     */
    'frameworkCache' => 'Phoenix\Cache\Framework\Impl\FileStoreImpl',
    /**
     * strict：严格模式
     * auto：自动匹配模式
     * 必须遵循 psr-4 标准
     */
    'interfaceMode' => 'strict',
    'injects' => array(
        'db' => 'Phoenix\PXPDO\Decorator',//注入类
        'Phoenix\PXPDO\Decorator' => array(
            'property' => array(
                'lazy' => false
            )
        ),
        'cache' => 'Phoenix\Cache\Impl\CacheImpl',
        'session' => 'Phoenix\Session\Impl\SessionImpl',
//        'injectProperty' => array(
//            'class' => 'App\Service\InjectProperty',
//            'property' => array(
//                'test' => 'test'
//            )
//        )
    ),
//	'sysPlugins' => array(
//		'App\SysPlugin\Named'
//	),
//	'aspectps' => array(//声明aop，必须指定具体的类名
//		'App\Admin\Aop\ManagerLog',
//		'App\ZhCN\Aop\IndexLog'
//	),
    404 => '404.html',
    500 => '50x.html',
    503 => '50x.html'
);
