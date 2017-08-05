<?php

namespace Phoenix\Interceptor;

if (!defined('IN_PX'))
    exit;

use Phoenix\Support\MsgHelper;
use Phoenix\SysPlugin\AbstractSysPlugin;
use Phoenix\Exception\FormatException;
use Phoenix\Log\Log4p as logger;

/**
 * Phoenix.AbstractInterceptor 控制器的顶级抽象类
 * Class AbstractInterceptor
 * @package Phoenix
 */
abstract class AbstractInterceptor {

    const VERSION = '3.2.6';

    public static $_instances;
    protected $_controllers;
    protected $_scanControllers = null;
    protected $_interfaceMapping;//接口类映射
    protected $_injectMapping = null;//注入映射
    protected $_singletonMapping = null;//singleton method
    protected $_rpcPublish = null;//json RPC
    protected $_apiPublish = null;//api publish
    protected $_aspectp;
    //cache的注入索引固化便于框架调用，不需要走反射。若手动更改过\Phoenix\Cache类结构，这里需要及时调整
    protected $_cacheInjectMappingHolder = array(
        'Phoenix\Cache\Framework\Impl\MemcacheStoreImpl' => array(
            'bundles' => array('dsn' => '@config')
        ),
        'Phoenix\Cache\Framework\Impl\MemcachedStoreImpl' => array(
            'bundles' => array('dsn' => '@config')
        ),
        'Phoenix\Cache\Framework\Impl\FileStoreImpl' => ''
    );
//    protected $_isInjectChange = false; //inject change notify
    protected $_isUseInjectMapping = true;
    protected $_runtimePack = '~runtime/';
    protected $_cacheablePack = '~cacheable/';
    protected $_classCacheId = null;
    protected $_injectMappingCacheId = null; //注入索引
    protected $_controllersCachePath = null;
    protected $_controllersCacheId = null; //控件缓存
    protected $_controllersGenericCacheId = null;
    protected $_singletonMappingCacheId = null;
    protected $_rpcPublishCacheId = null;
    protected $_apiPublishCacheId = null;
    protected $_controllerIndex = null; //小写的路由index
    protected $_interfaceMappingCacheId = null; //接口抽象类 类映射
    protected $_isExecInterceptor = false; //是否执行了拦截器
    protected $_interceptorChain; //拦截器堆栈
    protected $_isForwardFlag = false; //是否forward
    protected $_frameworkCache = null;
    protected $context;

    /**
     * 获取站点全局配置 c层
     */
    protected final function _getRouteConfig() {
        if (!isset($this->context['__ROUTE_CONFIG__'])) {
            $this->context['__ROUTE_CONFIG__'] = require_once APP_PATH . 'route.config.php';
            $this->context['__ROOT__'] = & $this->context['__ROUTE_CONFIG__']['root'];
            $this->context['__SUFFIX__'] = & $this->context['__ROUTE_CONFIG__']['suffix'];
            $this->context['__EXTENSIONS__'] = 'php';
        }
        if (!isset($this->context['__CHARSET__'])) {
            $this->context['__CHARSET__'] = & $this->context['__ROUTE_CONFIG__']['charset'];
        }
        if (!isset($this->context['__DOMAIN__'])) {
            $this->context['__DOMAIN__'] = & $this->context['__ROUTE_CONFIG__']['domain'];
            if (isset($this->context['__ROUTE_CONFIG__']['sysPlugins']) &&
                count($this->context['__ROUTE_CONFIG__']['sysPlugins']) > 0) {
                $this->context['__SYS_PLUGINS__'] = & $this->context['__ROUTE_CONFIG__']['sysPlugins'];
            }
            $this->context['__TAGLIB__'] = & $this->context['__ROUTE_CONFIG__']['taglib'];
        }
        if (!isset($this->context['__UPLOAD__'])) {
            $this->context['__UPLOAD__'] = & $this->context['__ROUTE_CONFIG__']['uploadDirectory'];
        }
        $this->context['__ROOT__'] = rtrim($this->context['__ROOT__'], '/') . '/';
        if (!isset($this->context['__ASSETS_PATH__'])) {
            //==视图相对路径
            $this->context['__ASSETS_PATH__'] = $this->context['__ROOT__']
                . $this->context['__ROUTE_CONFIG__']['assetsDirectory'] . '/';
            //静态 upload资源显示路径
            if (empty($this->context['__ROUTE_CONFIG__']['cdnDomain'])) {//cdn
                $this->context['__CDN__'] = $this->context['__ROOT__']
                    . $this->context['__UPLOAD__'] . '/';

                $this->context['__ASSETS_STATIC_PATH__'] = $this->context['__ASSETS_PATH__']
                    . $this->context['__ROUTE_CONFIG__']['staticDirectory'] . '/';
            } else {
                $this->context['__CDN__'] = $this->context['__ROUTE_CONFIG__']['cdnDomain'] . '/';

                $this->context['__ASSETS_STATIC_PATH__'] = $this->context['__ROUTE_CONFIG__']['cdnDomain'] . '/'
                    . $this->context['__ROUTE_CONFIG__']['staticDirectory'] . '/';
            }
        }

        if (!isset($this->context['__LANGUAGE_CONFIG__'])) {
            foreach ($this->context['__ROUTE_CONFIG__']['languages'] as $_k => $_v) {
                $this->context['__LANGUAGE_CONFIG__'][] = $_k;
            }
        }
        $this->context['__VENDOR__'] = $this->context['__ROOT__'] . 'vendor/';
        if (is_null($this->_injectMapping)) {
            $this->_injectMapping = $this->_cacheInjectMappingHolder;
        }
    }

    /**
     * 拦截工作器
     * @param $privateInterceptorName
     * @return bool true拦截通过 false拦截未通过
     * @throws FormatException
     */
    protected final function _worker($privateInterceptorName) {
        $_interceptorResult = true; //当为false时说明已拦截
        $this->context['__REAL_REDIRECT_CONTROLLER__'] = null; //默认转向为传入的页面模块
        //先全局
        $_globalRef = $this->context['__INTERCEPTORS__']['ref'];

        $this->_interceptorChain = array();

        if (isset($this->context['__INTERCEPTORS__']['stack'][$_globalRef])) {
            foreach ($this->context['__INTERCEPTORS__']['stack'][$_globalRef] as $_k => $_v) {
                if (count($_v) > 0) {//有存在项开始执行
                    $_boolExecFlag = true; //每一次始终标识进行检查
                    $_includeExists = isset($_v['includeRoute']) && count($_v['includeRoute']) > 0;
//                    $_exactMatchFromExclude = false;//排除项精确匹配
                    $_exactMatchFromInclude = false;//包含项精确匹配
                    //先查询排除项中__PHP_SELF__是否存在
                    if (isset($_v['excludeRoute']) &&
                        count($_v['excludeRoute']) > 0) {
                        if ($this->_inArray($this->context['__PHP_SELF__'], $_v['excludeRoute'])) {
                            $_boolExecFlag = false; //排除掉，不运行拦截器
                        } else if ($_includeExists &&
                            $this->_inArray($this->context['__PHP_SELF__'], $_v['includeRoute'])) {
                            //即时通配符排除,但是依旧级别没有完全匹配级别高
                            //排除精确匹配级别最高
                            //排除精确匹配 > 包含精确匹配 > 排除通配符 > 包含通配符
                            $_exactMatchFromInclude = true;//包含项精确匹配
                        } else {
                            //如果未找到，则查找有通配符选项
                            foreach ($_v['excludeRoute'] as $_er) {
                                if (strpos($_er, '*') !== false &&
                                    preg_match('/^' . str_replace(array('\/\*\*', '\*\*', '\*'),
                                            array('.*', '.*', '[^\/]*'),
                                            preg_quote($_er, '/'))
                                        . '$/Ui', $this->context['__PHP_SELF__'])) {
                                    $_boolExecFlag = false;
                                    break;
                                }
                            }
                        }
                    }
                    //如果未在排除项中，则检查是否存在于包含项中
                    if ($_boolExecFlag && $_includeExists) {
                        $_boolExecFlag = false; //每一次标识不在包含中
                        //全选或者存在，全选只在全局中有效
                        if ($_exactMatchFromInclude == true ||
                            $this->_inArray($this->context['__PHP_SELF__'], $_v['includeRoute']) ||
                            in_array('/**', $_v['includeRoute'])) {
//                            $_exactMatchFromInclude = true;
                            $_boolExecFlag = true; //发现包含，需要运行拦截器
                        } else {
                            //如果未找到，则查找有通配符选项
                            foreach ($_v['includeRoute'] as $_ir) {
                                if (strpos($_ir, '*') !== false &&
                                    preg_match('/^' . str_replace(array('\/\*\*', '\*\*', '\*'),
                                            array('.*', '.*', '[^\/]*'),
                                            preg_quote($_ir, '/'))
                                        . '$/Ui', $this->context['__PHP_SELF__'])) {
                                    $_boolExecFlag = true; //发现包含，需要运行拦截器
                                    break;
                                }
                            }
                        }
                    }
                    //如果$_boolExecFlag = true说明存在于包含中，需要运行拦截器
                    //拦截器代理免疫，aop无法切入拦截器中，否则拦截器无法正常工作
                    //拦截器类似于aop的一种(亦是监控方法的执行)，无须aop的切入
                    if ($_boolExecFlag) {
                        $this->_isExecInterceptor = true; //拦截器已匹配url
                        $_handler = self::$_instances->inject(
                            $this->context['__INTERCEPTORS__']['interceptor'][$_k],
                            true
                        );
                        //die(var_dump($_handler));
                        //必须返回非 true 才会被拦截
                        $_interceptorResult = $_handler->preHandle($this->context);
                        if (is_null($_interceptorResult)) {
                            $_interceptorResult = true;
                        }
                        if (true !== $_interceptorResult) {
//                            $_interceptorResult = false;
                            if (isset($this->context['__INTERCEPTORS__']['stack'][$_globalRef][$_k]['redirect']) &&
                                $this->context['__INTERCEPTORS__']['stack'][$_globalRef][$_k]['redirect']) {
                                $this->context['__INTERCEPTORS__']['redirect'] = $this->context['__INTERCEPTORS__']['stack'][$_globalRef][$_k]['redirect'];
                            }
                            break;
                        } else {
                            //已经匹配的拦截器堆栈
                            array_unshift($this->_interceptorChain, $_handler);
                        }
                    }
                }
            }
        } else {
            throw new FormatException(MsgHelper::get('0x00000005', $_globalRef), '0x00000005');
        }
        //全局运行正确
        if (true === $_interceptorResult) {
            //后私有
            if (!is_null($privateInterceptorName) &&
                isset($this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName]) &&
                strcasecmp($_globalRef, $privateInterceptorName) != 0) {//如果与全局重名将不执行
                //如果私有拦截器转向存在将覆盖全局转向
                if ($this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName]['redirect']) {
                    $this->context['__INTERCEPTORS__']['redirect'] = $this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName]['redirect'];
                    unset($this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName]['redirect']);
                }
                //私有拦截中includeRoute,excludeRoute失效
                foreach (array_keys($this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName]) as $_k) {
                    //全局中已存在，将不再执行
                    if (isset($this->context['__INTERCEPTORS__']['stack'][$_globalRef][$_k])) {
                        continue;
                    }
                    //运行注册的拦截器类
                    $_handler = self::$_instances->inject(
                        $this->context['__INTERCEPTORS__']['interceptor'][$_k],
                        true
                    );
                    $_interceptorResult = $_handler->preHandle($this->context);
                    if (is_null($_interceptorResult)) {
                        $_interceptorResult = true;
                    }
                    if (true !== $_interceptorResult) {
//                        $_interceptorResult = false;
                        //如果私有拦截有转向，将覆盖以上全部转向
                        if ($this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName][$_k]['redirect']) {
                            $this->context['__INTERCEPTORS__']['redirect'] = $this->context['__INTERCEPTORS__']['stack'][$privateInterceptorName][$_k]['redirect'];
                        }
                        break;
                    } else {
                        //已经匹配的拦截器链
                        array_unshift($this->_interceptorChain, $_handler);
                    }
                }
            }
        }
        //如果被拦截则需要转向
        if (true !== $_interceptorResult) {
            if (is_array($this->context['__INTERCEPTORS__']['redirect'])) {
                $this->context['__REAL_REDIRECT_CONTROLLER__'] = null;
                foreach ($this->context['__INTERCEPTORS__']['redirect'] as $_match => $_rd) {
                    if ($_match == '/') {
                        continue;
                    }
                    if (strpos($this->context['__PHP_SELF__'], $_match) !== false) {
                        $this->context['__REAL_REDIRECT_CONTROLLER__'] = $_rd;
                        break;
                    }

                }
                if (is_null($this->context['__REAL_REDIRECT_CONTROLLER__'])) {
                    if (isset($this->context['__INTERCEPTORS__']['redirect']['/'])) {
                        $this->context['__REAL_REDIRECT_CONTROLLER__'] = $this->context['__INTERCEPTORS__']['redirect']['/'];
                    } else {
                        throw new FormatException(MsgHelper::get('0x00000009', $this->context['__PHP_SELF__']), '0x00000009');
                    }
                }
            } else {
                $this->context['__REAL_REDIRECT_CONTROLLER__'] = $this->context['__INTERCEPTORS__']['redirect'];
            }
        }

        return $_interceptorResult;
    }

    /**
     * aop监控
     * @return mixed
     */
    protected final function _monitoringAspectp() {
        //aop缓存
        $_aspectsCacheId = $this->_runtimePack . 'aspectp';
        $_isAspectsExists = $this->_frameworkCache->exists($_aspectsCacheId);
        if (isset($this->context['__ROUTE_CONFIG__']['aspectps']) &&
            is_null($this->_aspectp) &&
            (
                false === $_isAspectsExists ||
                false === ($this->_aspectp = $this->_frameworkCache->get($_aspectsCacheId))
            )
        ) {
            self::$_instances->dissectionAspects();
            if (false === $_isAspectsExists) {
                $this->_frameworkCache->set($_aspectsCacheId, $this->_aspectp);
            } else {
                logger::error('aspectp cache misses.');
            }
        }

        $this->_isUseInjectMapping = !PX_DEBUG; //调试期不开注入索引

        $this->_classCacheId = $this->_runtimePack . 'classes';
//        $this->_injectMappingCacheId = $this->_runtimePack . $this->_data['__CORE_MAPPING_PATH__']
//            . '/injectmapping';
        $this->_injectMappingCacheId = $this->_runtimePack . '~inject/';
        $this->_singletonMappingCacheId = $this->_runtimePack . 'singleton';
        $this->_rpcPublishCacheId = $this->_runtimePack . 'rpcpublish';
        $this->_apiPublishCacheId = $this->_runtimePack . 'apipublish';
//        $this->_controllersCacheId = $this->_runtimePack . $this->_data['__CORE_MAPPING_PATH__']
//            . '/controllers';

        if (isset($this->context['__CONTROLLER_INDEX__'])) {
            $this->_controllerIndex = strtolower($this->context['__CONTROLLER_INDEX__']);
            if (strcasecmp($this->_controllerIndex, 'rpc') !== 0) {
                $this->_controllersCachePath = $this->_runtimePack . $this->context['__CORE_MAPPING_PATH__']. '/';
                $this->_controllersCacheId = $this->_controllersCachePath . $this->_controllerIndex;
                $this->_controllersGenericCacheId = $this->_controllersCachePath . '@';
            }
        }
        $this->_interfaceMappingCacheId = $this->_runtimePack . 'interfacemapping';

        return $_isAspectsExists;
    }

    /**
     * http状态码
     * @param type $status
     */
    protected final function _httpStatus($status) {
        $_http = array (
            100 => 'HTTP/1.1 100 Continue',
            101 => 'HTTP/1.1 101 Switching Protocols',
            200 => 'HTTP/1.1 200 OK',
            201 => 'HTTP/1.1 201 Created',
            202 => 'HTTP/1.1 202 Accepted',
            203 => 'HTTP/1.1 203 Non-Authoritative Information',
            204 => 'HTTP/1.1 204 No Content',
            205 => 'HTTP/1.1 205 Reset Content',
            206 => 'HTTP/1.1 206 Partial Content',
            300 => 'HTTP/1.1 300 Multiple Choices',
            301 => 'HTTP/1.1 301 Moved Permanently',
            302 => 'HTTP/1.1 302 Found',
            303 => 'HTTP/1.1 303 See Other',
            304 => 'HTTP/1.1 304 Not Modified',
            305 => 'HTTP/1.1 305 Use Proxy',
            307 => 'HTTP/1.1 307 Temporary Redirect',
            400 => 'HTTP/1.1 400 Bad Request',
            401 => 'HTTP/1.1 401 Unauthorized',
            402 => 'HTTP/1.1 402 Payment Required',
            403 => 'HTTP/1.1 403 Forbidden',
            404 => 'HTTP/1.1 404 Not Found',
            405 => 'HTTP/1.1 405 Method Not Allowed',
            406 => 'HTTP/1.1 406 Not Acceptable',
            407 => 'HTTP/1.1 407 Proxy Authentication Required',
            408 => 'HTTP/1.1 408 Request Time-out',
            409 => 'HTTP/1.1 409 Conflict',
            410 => 'HTTP/1.1 410 Gone',
            411 => 'HTTP/1.1 411 Length Required',
            412 => 'HTTP/1.1 412 Precondition Failed',
            413 => 'HTTP/1.1 413 Request Entity Too Large',
            414 => 'HTTP/1.1 414 Request-URI Too Large',
            415 => 'HTTP/1.1 415 Unsupported Media Type',
            416 => 'HTTP/1.1 416 Requested range not satisfiable',
            417 => 'HTTP/1.1 417 Expectation Failed',
            500 => 'HTTP/1.1 500 Internal Server Error',
            501 => 'HTTP/1.1 501 Not Implemented',
            502 => 'HTTP/1.1 502 Bad Gateway',
            503 => 'HTTP/1.1 503 Service Unavailable',
            504 => 'HTTP/1.1 504 Gateway Time-out',
            505 => 'HTTP/1.1 505 HTTP Version Not Supported'
        );
        switch ($status) {
            case 404 :
                header($_http[$status]);
                header('Status: 404 Not Found');
                break;
            default :
                header($_http[$status]);
                break;
        }
        if (isset($this->context['__ROUTE_CONFIG__'][$status]) &&
            !empty($this->context['__ROUTE_CONFIG__'][$status])) {
            include ROOT_PATH . ltrim($this->context['__ROUTE_CONFIG__'][$status], '/');
        } else {
            echo $_http[$status];
        }
        exit;
    }

    /**
     * 类名解析出url
     * 将namespace中第一项清洗，20150803
     * @param $clazz
     * @param $replaceNamespace
     * @return null|string
     */
    protected final function _parseUrlWithClassName($clazz, $replaceNamespace) {
        $clazz = str_replace($replaceNamespace, '', $clazz);
        if (substr_count($clazz, '\\') > 1) {
            $clazz = preg_replace('/^[^\\\\]+\\\\(.+)\\\\[^\\\\]+$/', '$1', $clazz);
            if (substr_count($clazz, '\\') > 1) {
                $clazz = explode('\\', $clazz);
                foreach ($clazz as $_k => $_w) {
                    $clazz[$_k] = $this->_lcfirst($_w);
                }
                return implode('/', $clazz);
            }
            return $this->_lcfirst($clazz);
        }
        return null;
    }

    /**
     * 格式化内存使用
     * @param type $size
     * @return type
     */
    protected function _convert($size) {
        if ($size == 0) {
            return 0;
        }
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return round($size / pow(1024, ($i = floor(log($size, 1024)))), 3)
        . ' ' . $unit[$i];
    }

    /**
     * 导入系统插件
     * 系统插件不需要aop
     */
    protected function _importSysPlugin() {
        if (isset($this->context['__SYS_PLUGINS__'])) {
            $_tmp = null;
            foreach ($this->context['__SYS_PLUGINS__'] as $_sysPluginClass) {
                $_tmp = self::$_instances->inject($_sysPluginClass, true);
                if ($_tmp instanceof AbstractSysPlugin) {
                    $this->_arySysPlugins[$_sysPluginClass] = $_tmp;
                } else {
                    $_tmp = null;
                }
            }
        }
    }

    /**
     * 执行插件指定的不带参数方法
     * @param type $method
     */
    protected function _execSysPlugin($method) {
        if (!is_null($this->_arySysPlugins)) {
            foreach ($this->_arySysPlugins as $_handler) {
                $_handler->$method();
            }
        }
    }

    /**
     * 不包含通配符以及不区分大小写的数组查找
     * @param type $needle
     * @param type $haystack
     * @return boolean
     */
    private function _inArray($needle, $haystack) {
        if (count($haystack) == 0) {
            return false;
        }
        foreach ($haystack as $_v) {
            if (strpos($_v, '*') === false &&
                strcasecmp($needle, $_v) == 0) {
                return true;
            }
        }
        return false;
    }

    private function _lcfirst($str) {
        if (false === function_exists('lcfirst')) {
            return strtolower(substr($str, 0, 1)) . substr($str, 1);
        }
        return lcfirst($str);
    }

    public static final function mime($ext) {
        $_mimeTypes = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
        if (isset($_mimeTypes[$ext])) {
            return $_mimeTypes[$ext];
        }
        return 'application/octet-stream';
    }

    public function & __get($name) {
        $_r = null;
        if (isset($this->context[$name])) {
            $_r = & $this->context[$name];
        }
        return $_r;
    }

    public function __set($name, $value) {
        return $this->context[$name] = $value;
    }

}
