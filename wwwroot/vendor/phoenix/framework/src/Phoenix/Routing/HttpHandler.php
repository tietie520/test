<?php

namespace Phoenix\Routing;

if (!defined('IN_PX'))
    exit;

use Phoenix\Support\MsgHelper;
use Phoenix\Log\Log4p as logger;

/**
 * Handler类不走路由，不会解析路由进行分发，必须映射到具体的类
 * Handler只运行指定类，故速度比Controller要快的多
 * 可以使用拦截器，如果使用拦截器的话需要在对应包中interceptors.config.php注册
 */
class HttpHandler extends ProxyFactory {

    const VERSION = '3.2.4';

    private $_aryHookApiUri = null;
    private $_currentBuffer = null;

//    private function __construct() {
//        
//    }

    public function execute(Array & $data, $isHookApi = false) {
        $this->_currentBuffer = null;
        if ($isHookApi) {
//            $this->_data['__HOOK_API_FLAG__'] = true;
            if (is_null($this->_aryHookApiUri)) {
                $this->_aryHookApiUri = array();
            }
            array_push($this->_aryHookApiUri, $this->context);
        }
        if (!empty($data)) {//除了传参取址，赋值的同时也必须引用
            $this->context = & $data;
        }
        //赋值config信息
        parent::_getRouteConfig();

        $this->_importSysPlugin();
        $this->_execSysPlugin('init');

        $this->_setApplicationContext();

        $this->_execSysPlugin('afterParseRoutes');
        //取出模块对应的类
        if (is_file(APP_PATH . $this->context['__ROUTE_CONFIG__']['handlerDirectory'] . DIRECTORY_SEPARATOR
            . implode(DIRECTORY_SEPARATOR, $this->context['__ARY_HANDLER_PATHS__'])
            . '.php')) {

            if (is_null($this->_injectMapping)) {
                //logger::debug(count($this->_injectMapping));
                $this->_injectMapping = $this->_cacheInjectMappingHolder;
            }
            if (is_null($this->_frameworkCache)) {
                $this->_frameworkCache = $this->inject($this->context['__ROUTE_CONFIG__']['frameworkCache']);
            }

            $this->_monitoringAspectp();

            //是否开启注入索引
            //$_isUseInjectMapping = true;
//            $_isUseInjectMapping = !PX_DEBUG; //调试期不开注入索引
//            if ($_isUseInjectMapping &&
//                count($this->_injectMapping) <= 1 &&
//                false === ($this->_injectMapping = $this->_cache->get($this->_injectMappingCacheId))) {
//                $this->_injectMapping = $this->_cacheInjectMappingHolder;
//            }

            ob_start();
            $this->_processModelClass($this->inject($this->context['__HANDLER_CLASS_NAME__']));
            $this->_currentBuffer = ob_get_clean();

//            if ($_isUseInjectMapping && $this->_isInjectChange) {
//                $_cache->set($this->_injectMappingCacheId, $this->_injectMapping);
//            }

            if ($isHookApi) {//hook api标识
                if (count($this->_aryHookApiUri) > 0) {
                    $this->context = array_pop($this->_aryHookApiUri);
                }
                if (count($this->_aryHookApiUri) == 0) {
                    unset($this->_aryHookApiUri);
                    $this->_aryHookApiUri = null;
                }
                return $this->_currentBuffer;
            } else {
                echo $this->_currentBuffer;
            }
        }
        //die(var_dump($this->_data));
        //$this->_checkResourcesOccupation();
        //$this->_data = null;
        //unset($this->_data);
    }

    /**
     * 测试handler运行时间及内存使用，并写入调试日志
     */
    private function _checkResourcesOccupation() {
        $this->context['__E_MEMORY_USE__'] = memory_get_usage();
        $this->context['__E_RUNTIME__'] = microtime(true);
        $_output = $this->context['__PHP_SELF__'] . ' 执行时间：'
            . ($this->context['__E_RUNTIME__'] - $this->context['__S_RUNTIME__'])
            . ' 内存使用：'
            . ($this->_convert($this->context['__E_MEMORY_USE__'])
                . ' - ' . $this->_convert($this->context['__S_MEMORY_USE__'])
                . ' = ' . $this->_convert($this->context['__E_MEMORY_USE__'] - $this->context['__S_MEMORY_USE__']));

        logger::info($_output);
    }

    /**
     * 加载页面类 M层
     * @param \Phoenix\Routing\IHttpHandler $handler 如果加入aop则不能限制handler的类型
     * 			也有可能为(Phoenix_ProxyHandler)
     */
    //private function _processModelClass(\Phoenix\IHttpHandler & $handler) {
    private function _processModelClass($handler) {
        //$handler->boolSubjectInterceptor();
        $_handlerInstances = null;
        //\Phoenix\IHttpHandler中才有 processRequest，可以判断是否为代理类
        $_isProxy = method_exists($handler, 'processRequest') ? false : true;
        if ($_isProxy) {
            $_handlerInstances = $handler->currentInstances();
        } else {
            $_handlerInstances = $handler;
        }

        //抽出包里面的路由配置
        $this->context['__INTERCEPTORS_CONFIG_PATH__'] = APP_PATH
            . $this->context['__ARY_HANDLER_PATHS__'][0] . DIRECTORY_SEPARATOR . 'interceptors.config.php';
        if (is_file($this->context['__INTERCEPTORS_CONFIG_PATH__'])) {
            $this->context['__INTERCEPTORS__'] = require_once $this->context['__INTERCEPTORS_CONFIG_PATH__'];
            if (true !== ($_ic = parent::_worker(
                isset($this->_injectMapping[$this->context['__HANDLER_CLASS_NAME__']]['interceptor'])
                    ? $this->_injectMapping[$this->context['__HANDLER_CLASS_NAME__']]['interceptor']
                    : null
            )) && !is_null($_ic)) {
                //handler被拦截一律返回被拦截json状态
                die(MsgHelper::json(10002));
            }
        }

        if ($_isProxy) {
            $_currentProperties = array($this->context);
            self::$_instances->invoke($this->context['__HANDLER_CLASS_NAME__'],
                'processRequest',
                $_currentProperties); //包裹成一个参数传入
            $this->context = & $handler->getProxyData();
        } else {
            $_handlerInstances->processRequest($this->context);
        }
        //$handler->processRequest($this->_data);
        if (count($this->_interceptorChain) > 0) {
            foreach ($this->_interceptorChain as $_handler) {
                $_handler->afterCompletion($this->context);
            }
        }
    }

    /**
     * 加载配置
     */
    private function _setApplicationContext() {
        //handler不走路由，只保留具体类的地址，其他参数使用get，post获取
        $this->context['__CONTROLLER_MAPPING__'] = array_shift($this->context['__PATHS__']);
        $this->context['__ARY_HANDLER_PATHS__'] = explode('.', $this->context['__CONTROLLER_MAPPING__']);

        //执行页面真实的分发，用于拦截器的执行
        //$this->_data['__CONTROLLER_INDEX__'] = 'handler';
        $this->context['__CONTROLLER_MAPPING__'] = '/' . $this->context['__CONTROLLER_MAPPING__'];
//        if (false !== $this->_isForwardFlag) {
//            $this->_data['__PHP_SELF__'] = $this->_data['__CONTROLLER_INDEX__']
//                    . rtrim($this->_data['__CONTROLLER_MAPPING__'], '/');
//        } else {
//            $this->_isForwardFlag = false;
//        }
        if (false !== $this->_isForwardFlag) {
            $this->_isForwardFlag = false;
        }

        $_lowerCI = lcfirst($this->context['__ARY_HANDLER_PATHS__'][0]);

        if (false === ($this->context['__PACKAGE__'] = array_search($_lowerCI,
                $this->context['__ROUTE_CONFIG__']['viewConstant']))) {
            $this->context['__PACKAGE__'] = $_lowerCI;
        }

        $this->context['__CORE_MAPPING_PATH__'] = $_lowerCI;

        $this->context['__ASSETS_PATH__'] .= $this->context['__CORE_MAPPING_PATH__'] . '/';

        $this->_injectMappingCacheId .= 'handler';

        $this->context['__HANDLER_CLASS_NAME__'] = $this->context['__ROUTE_CONFIG__']['namespace']
            . "{$this->context['__ROUTE_CONFIG__']['handlerDirectory']}\\"
            . implode('\\', $this->context['__ARY_HANDLER_PATHS__']);

        $this->context['__REQUEST_MAPPING__'] = null;
        $this->context['__PHP_SELF__'] = $this->context['__CONTROLLER_INDEX__']
            . $this->context['__CONTROLLER_MAPPING__'];
        $this->context['__VC__'] = $this->context['__CORE_MAPPING_PATH__'];
        $this->context['__RC__'] = & $this->context['__ROUTE_CONFIG__'];
        $this->context['__CI__'] = & $this->context['__CONTROLLER_INDEX__'];
        $this->context['__CM__'] = & $this->context['__CONTROLLER_MAPPING__'];
        $this->context['__RM__'] = & $this->context['__REQUEST_MAPPING__'];
        $this->context['__ASSETS__'] = & $this->context['__ASSETS_PATH__'];
        $this->context['__STATIC__'] = & $this->context['__ASSETS_STATIC_PATH__'];
        $this->context['__RAD__'] = & $this->context['__REQUIRE_ABSOLUTE_DIR__'];
    }

    public static function process(Array & $data = array('__CONTROLLER_INDEX__' => 'handler',
        '__PATHS__' => array()), $isHookApi = false) {
        if (is_null(static::$_instances)) {
            static::$_instances = new self();
        }
        return static::$_instances->execute($data, $isHookApi);
    }

}
