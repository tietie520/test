<?php

namespace Phoenix\Routing;

if (!defined('IN_PX'))
    exit;

use Phoenix\Foundation\Application;
use Phoenix\Interceptor\AbstractInterceptor;
use Phoenix\Exception\FormatException;
use Phoenix\Support\MsgHelper;
use Phoenix\Support\Array2Xml;
use Phoenix\Support\Helpers;
use Phoenix\Log\Log4p as logger;

/**
 * Phoenix\Controller 所有前台页面访问的单一入口
 * 所有前台页面必须通过这个类进行访问
 * 解析相应的类并加载指定的模板文件
 * 类文件统一使用命名空间，并存放在 "/core/命名空间/xxx.php" 命名的路径中
 * 变量名“__xxx_xx__”为框架变量。页面类使用了 __InjectData 注入，才可以调用
 * 页面(view层)始终可以调用
 * 框架变量如果污染可能会导致框架运行不稳定
 * 任何注入带默认值的参数必须放在最后，因为php函数或方法只允许前面参数无默认值，后面参数有默认值
 * 否则会造成注入异常，例如：method($__Route = '..', $parameter)这是错误的
 * 应该写成：method($parameter, $__Route = '..')
 * 需要注入的类中 __construct 应该设为 public 级别
 * v3.2.3 _findController()方法中对根下的*泛匹配逻辑顺序略作调整(在未直接命中路由然后再查询泛匹配)
 * 		_pushUrlParameter() 修改一个foreach作用域 2013-10-22
 * v3.2.4 debug模式下监控到文件改动则直接清空controller,inject缓存并重新生成，拦截器拦截responsebody返回json
 * v3.2.5 页面返回值支持forward转向
 * v3.2.6 修正forward转向数据共享及保留最初url分析 __PHP_SELF__, __CI__, __CM__, __RM__ 保留为第一次入口 \Phoenix\Session 当值设为null时删除键
 * v3.2.7 修正3.2.6 的forward用户数据共享
 * v3.2.10 head当做get处理，响应一些负载均衡中的请求，修正
 * v3.2.11 增加languagesViewSingleDirectory，前台可以在单一目录下做i18n
 * header('Cache-control: private'); //解决后退页面过期或不存在的情况
 * header('Content-type: text/html; charset=utf-8');
 */

class Controller extends AbstractInterceptor {

    const VERSION = '3.2.13';

    private $_responseBody = false; //返回的json数据
    private $_aryHookApiUri = null;
    private $_currentBuffer = null;
    private $_genericController = null; //泛路由
    protected $_arySysPlugins = null;

    /**
     * 主函数
     * @param array $data
     * @param bool $isHookApi
     * @return null|string
     */
    public function run(Array & $data, $isHookApi = false) {
        $this->_currentBuffer = null;
        if ($isHookApi) {
//            $this->_data['__HOOK_API_FLAG__'] = true;
            if (is_null($this->_aryHookApiUri)) {
                $this->_aryHookApiUri = array();
            }
            array_push($this->_aryHookApiUri, $this->context);
        }
        if ($this->_isForwardFlag) {//forward转向保留最初的url分析
            //注入转向后的url分析
            $this->context['__PATHS__'] = $data['__PATHS__'];
            $this->context['__PACKAGE__'] = $data['__PACKAGE__'];
        } else if (!empty($data)) {//除了传参取址，赋值的同时也必须引用
            $this->context = & $data;
        }

        //赋值config信息
        parent::_getRouteConfig();

        $this->_importSysPlugin();
        $this->_execSysPlugin('init');

        //初始化配置
        $this->_setApplicationContext();
        $this->_execSysPlugin('afterParseRoutes');
//        logger::debug($this->_data);
//        logger::debug($this->_currentBuffer);
        //执行页面相关类
        //cache的注入索引已在父类(\Phoenix\Interceptor\AbstractInterceptor)中固化
        if (is_null($this->_injectMapping)) {
            //logger::debug(count($this->_injectMapping));
            $this->_injectMapping = $this->_cacheInjectMappingHolder;
        }
        if (is_null($this->_frameworkCache)) {
            $this->_frameworkCache = self::$_instances->inject($this->context['__ROUTE_CONFIG__']['frameworkCache']);
        }
        ob_start();
        $this->_runController();
        $this->_currentBuffer = ob_get_clean();

        if (isset($this->context['__CACHEABLE__'])) {
            $this->_frameworkCache->mode($this->context['__CACHEABLE__'][2])
                ->expires($this->context['__CACHEABLE__'][1])
                ->set(
                    $this->context['__CACHEABLE__'][0]
                    . $this->_getCacheablePageName(),
                    $this->_currentBuffer
//                                , ob_get_contents()
                );
//                ob_end_flush();
        }

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
            if (isset($this->context['__CURRENT_PROPERTIES__']['__Expires'])) {
                $_time = date('D, d M Y H:i:s',
                        time() + intval($this->context['__CURRENT_PROPERTIES__']['__Expires'])) . ' GMT';
                header("Expires: {$_time}");
            }
            if (isset($this->context['__CURRENT_PROPERTIES__']['__Etag'])) {
                if (!isset($this->context['__Etag'])) {
                    $this->context['__Etag'] = md5($this->_currentBuffer);
                }
                if (isset($_SERVER['HTTP_IF_NONE_MATCH'])
                    && strcasecmp($this->context['__Etag'],
                        $_SERVER['HTTP_IF_NONE_MATCH']) == 0) {
                    $this->_httpStatus(304);
                }
                header("Etag: {$this->context['__Etag']}");
            }
            if (isset($this->context['__CURRENT_PROPERTIES__']['__LastModified'])
                && isset($this->context['__LastModified'])) {
                $this->context['__LastModified'] = date('D, d M Y H:i:s',
                        $this->context['__LastModified']) . ' GMT';
                if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                    && strcasecmp($this->context['__LastModified'],
                        $_SERVER['HTTP_IF_NONE_MATCH']) == 0) {
                    $this->_httpStatus(304);
                }
                header("Last-Modified: {$this->context['__LastModified']}");
            }

            echo $this->_currentBuffer;
        }

        $this->_outDebug();
    }

//	public function __destruct() { //经测试for循环中内存可以共享，所以不主动回收，由php自行管理
//		$this->_data = $this->_controllers = $this->_injectMapping = null;
//		unset($this->_injectMapping, $this->_controllers, $this->_data);
//	}

    protected final function _createCache($useRoute = true) {
        //$this->_frameworkCache->gc();
        $this->_monitoringAspectp();
        /**
         * 使用memcache缓存情况下使用框架缓存及注入索引比单纯的反射性能至少提升20%，基本符合PHP反射带来的性能损耗
         * 硬盘缓存情况下由于机械硬盘的io限制，但也比单纯的反射性能略有提升
         * 框架默认使用注入索引
         */
        //是否开启注入索引

        //strict切换到auto模式，如果interface不存在，则清空缓存
        if (strcmp($this->context['__ROUTE_CONFIG__']['interfaceMode'], 'auto') == 0 &&
            false === ($this->_interfaceMapping =
                $this->_frameworkCache->get($this->_interfaceMappingCacheId))) {
            $this->_frameworkCache->gc();
            $this->_injectMapping = $this->_cacheInjectMappingHolder;
        }

        if (false === ($this->_singletonMapping =
            $this->_frameworkCache->get($this->_singletonMappingCacheId))) {
//            $this->_frameworkCache->gc();
        }

        $_classList = null;

        if ($useRoute && false === $this->_frameworkCache->exists($this->_controllersCacheId) &&
            false === $this->_frameworkCache->exists($this->_controllersGenericCacheId)) {
            $_classList = $this->_scan();
        }
//        logger::debug($_classList);
//        die(var_dump($this->_interfaceMapping));
        if (PX_DEBUG) {//如果开发模式下类有更改，自动更新缓存
            if (count($_classList) == 0 &&
                false === ($_classList = $this->_frameworkCache->get($this->_classCacheId))) {
                $_classList = array();
            }
            $_aryScan = $this->_scanControllers(APP_PATH, $_classList);
            $_classListChg = $_aryScan['class'];
            if (count($_classListChg) > 0) {
                $this->_frameworkCache->gc();

                //重复以上步骤
                $this->_injectMapping = $this->_cacheInjectMappingHolder;

                $this->_aspectp = null;
                $this->_monitoringAspectp();
                $this->_scan();
            }
            $_classList = $_aryScan = null;
            unset($_classList, $_aryScan);
        }
        $this->_packControllerProvider();
    }

    /**
     * 运行模块类
     * @throws FormatException
     */
    private function _runController() {
        $this->_createCache();

        //用于修改比对的缓存文件
        $_corePath = APP_PATH . $this->context['__CORE_MAPPING_PATH__'];
        //缓存未命中进入独立剖析模式，输出致命错误日志，发现次日志应立即进行缓存及程序自检
        $_hitControllerCacheFlag = true;
        if (is_null($this->_controllers) &&
            false === ($this->_controllers = $this->_frameworkCache->get($this->_controllersCacheId))
        ) {
            //$this->_httpStatus(503);
            logger::fatal('controllers cache misses.');
            $_hitControllerCacheFlag = false;
            /**
             * 在高并发缓存未命中的情况下根据包进行路由并临时剖析类提供服务(依赖cpu)
             * 提高框架的健壮性
             */
            $_tmpFolderOrFile = $_corePath . DIRECTORY_SEPARATOR
                . ucfirst($this->context['__CONTROLLER_INDEX__']);

            if (is_file($_tmpFolderOrFile . '.php')) {
                self::$_instances->dissection($this->context['__ROUTE_CONFIG__']['namespace']
                    . $this->context['__CORE_MAPPING_PATH__']
                    . '\\' . ucfirst($this->context['__CONTROLLER_INDEX__']));
            } else {
                $_aryScan = $this->_scanControllers(APP_PATH);
                if (strcmp($this->context['__ROUTE_CONFIG__']['interfaceMode'], 'auto') == 0) {
                    $this->_interfaceMapping = $_aryScan['interface'];
                }
            }
            $this->_packControllerProvider();
        }
        $this->_scanControllers = null;
//        die(var_dump($this->_injectMapping));

        //未找到则判定为404，在命中缓存的情况下更新缓存
        if ((false === $this->_findController()) && $_hitControllerCacheFlag) {
            logger::warn("{$this->context['__PHP_SELF__']} is not found.", true);
//            $this->_frameworkCache->set($this->_controllersCacheId, $this->_controllers);
        }
        $this->_execSysPlugin('afterReadControllers');

//        logger::debug($this->_controllers);
//        die(var_dump($this->_controllers));
        //logger::debug($this->_injectMapping);
        if ($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
            [$this->context['__ACCEPT_INDEX__']]
            [$this->context['__METHOD__']]['__CONTROLLER_CLASS__'] != 404) {
            //装配参数
            $this->_pushUrlParameter();

            $this->_execSysPlugin('afterPushUrlParameter');

            //运行拦截器
            $this->_interceptorsWorker();
            //运行控制层
            //die(var_dump($this->_controllers));
            $this->_controllerLoader();
            //如果设置了forward，此时已转向，如果第一个url未被拦截则执行第二个url
            //如果第一个已拦截，则不再运行拦截器，但是依旧执行第一个url拦截的后续方法
        } else {
            //Controller不存在时拦截器依旧运行
            $this->_interceptorsWorker();
        }
        //logger::debug($this->__RM__);
        if (count($this->_interceptorChain) > 0) {
            foreach ($this->_interceptorChain as $_handler) {
                if (true !== ($_ic = $_handler->postHandle($this->context)) && !is_null($_ic)) {
                    $this->_interceptorRedirect($_ic);
                }
            }
        }
//        logger::debug($this->_controllers);
        //die(var_dump($this->_data['__VIEW__']));
        //判断是否没有设定页面类
        if (is_null($this->context['__REQUEST_MAPPING__'])) {
            $this->context['__REQUEST_MAPPING__'] = trim($this->context['__CONTROLLER_INDEX__']
                . $this->context['__CONTROLLER_MAPPING__'], '/');
        }
        if (is_null($this->context['__VIEW__'])) {
            $this->context['__VIEW__'] = $this->context['__REQUEST_MAPPING__'];
        }
        $_viewNull = false;
        if (!isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
            [$this->context['__ACCEPT_INDEX__']]
            [$this->context['__METHOD__']]['__ResponseBody']) &&
            !($_viewNull = strcmp($this->context['__VIEW__'], 'null') == 0)) {
            $this->context['__VIEW_ABSOLUTE_PATH__'] = $this->context['__VIEW_ABSOLUTE_PATH__']
                . $this->context['__VIEW__'] . $this->context['__TAGLIB__'];
        }
//        die(var_dump($this->_data['__VIEW_ABSOLUTE_PATH__']));
//        die(var_dump($this->_data));
        //die(var_dump($this->_controllers));
//        logger::debug($this->context);

        if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__ResponseBody']) ||
            $_viewNull ||
            is_file($this->context['__VIEW_ABSOLUTE_PATH__'])) {

            $this->_execSysPlugin('beforeViewLoader');

            // if (isset($this->_data['__CACHEABLE__']))
//                ob_start();
            if (isset($this->context['__ACCEPT__'])) {
                header("Content-type: {$this->context['__ACCEPT__']}; charset={$this->context['__CHARSET__']}");
            }

            if (!isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__ResponseBody'])) {
                if (!$_viewNull) {
                    include $this->context['__VIEW_ABSOLUTE_PATH__']; //加载运行模板
                }
                //视图加载完成的拦截器执行
                if (count($this->_interceptorChain) > 0) {
                    foreach ($this->_interceptorChain as $_handler) {
                        $_handler->afterCompletion($this->context);
                    }
                }
            } else {
                if (!isset($this->context['__ACCEPT__'])) {
                    throw new FormatException(MsgHelper::get('0x00000008',
                        $this->context['__PHP_SELF__']),
                        '0x00000008');
                }
                if (!is_null($this->_responseBody)) {
                    switch ($this->context['__ACCEPT__']) {
                        case 'application/xml' :
                            if (is_array($this->_responseBody)) {
                                echo Array2Xml::createXML($this->_responseBody,
                                    $this->context['__ROUTE_CONFIG__']['restXmlRootTag']);
                            } else {
                                throw new FormatException(MsgHelper::get('0x00000007',
                                    $this->context['__PHP_SELF__']),
                                    '0x00000007');
                            }
                            break;
                        case 'application/json' :
                        default :
                            echo Helpers::jsonEncode($this->_responseBody);
                            break;
                    }
                }
            }
        } else {
            //抛出路径不存在错误，404
            if (PX_DEBUG) {
                //抛出路由错误
                throw new FormatException(MsgHelper::get('0x00000006',
                    $this->context['__VIEW_ABSOLUTE_PATH__']),
                    '0x00000006');
            } else {
                $this->_httpStatus(404); //其他一律转到404
            }
        }
//        logger::debug($this->_injectMapping);

        //die(var_dump($this->_data));
        //die(var_dump($this->_controllers));
    }

    private function _packControllerProvider() {
        if (!is_null($this->_scanControllers) &&
            isset($this->_scanControllers[$this->context['__CORE_MAPPING_PATH__']][$this->_controllerIndex])) {
            $this->_controllers = $this->_scanControllers[$this->context['__CORE_MAPPING_PATH__']][$this->_controllerIndex];
            if (is_null($this->_controllers)) {
                $this->_controllers = false;//设置为未找到，与从缓存取出状态同步
            }
            if (isset($this->_scanControllers[$this->context['__CORE_MAPPING_PATH__']]['*'])) {
                $this->_genericController =
                    $this->_scanControllers[$this->context['__CORE_MAPPING_PATH__']]['*'];
            }
        }
    }

    /**
     * 扫描
     * @param string $corePath
     * @return mixed
     */
    private function _scan($corePath = APP_PATH) {
//        logger::debug(11);
        $_aryScan = $this->_scanControllers($corePath);

        $this->_frameworkCache->set($this->_classCacheId, $_aryScan['class']);
        if (strcmp($this->context['__ROUTE_CONFIG__']['interfaceMode'], 'auto') == 0) {
            $this->_frameworkCache->set($this->_interfaceMappingCacheId, $_aryScan['interface']);
            $this->_interfaceMapping = $_aryScan['interface'];
        }
//        $this->_frameworkCache->set($this->_controllersCacheId, $this->_controllers);
        if (!is_null($this->_scanControllers)) {
            foreach ($this->_scanControllers as $_package => $_controllers) {
                foreach ($_controllers as $_file => $_aryControllers) {
                    if (strcasecmp($_file, '*') == 0) {
                        $_file = '@';
                    }
                    $this->_frameworkCache->set($this->_runtimePack . $_package . '/' . strtolower($_file),
                        $_aryControllers);
                }
            }
        }
//        if (!is_null($this->_singletonMapping)) {
//            $this->_frameworkCache->set($this->_singletonMappingCacheId, $this->_singletonMapping);
//        }
        if (!is_null($this->_rpcPublish)) {
            $this->_frameworkCache->set($this->_rpcPublishCacheId, $this->_rpcPublish);
        }
        if (!is_null($this->_apiPublish)) {
            $this->_frameworkCache->set($this->_apiPublishCacheId, $this->_apiPublish);
        }
        return $_aryScan['class'];
    }

    /**
     * 运行拦截器
     */
    private function _interceptorsWorker() {
        //如果拦截器配置文件存在
        //执行页面类运行前拦截器
        if (false === $this->_isExecInterceptor &&
            is_file($this->context['__INTERCEPTORS_CONFIG_PATH__'])) {
            $this->context['__INTERCEPTORS__'] = require_once $this->context['__INTERCEPTORS_CONFIG_PATH__'];
            $_interceptor = null;
            if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__INTERCEPTOR__'])) {
                $_interceptor = isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                    [$this->context['__ACCEPT_INDEX__']]
                    [$this->context['__METHOD__']]['__INTERCEPTOR__']);
            }
            if (true !== ($_ic = parent::_worker($_interceptor))) {
                $this->_interceptorRedirect($_ic);
            }
        }
    }

    /**
     * 拦截器转向
     * @param $interceptorResult
     * @return bool
     */
    private function _interceptorRedirect($interceptorResult) {
        if (is_null($interceptorResult)) {
            return true;
        }
        //被拦截后返回转入指定的模块并传入一个用于返回当前页面的url值
        //如果是纯数字，如 404 500 503等
        $_isResponseBody = isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
            [$this->context['__ACCEPT_INDEX__']]
            [$this->context['__METHOD__']]['__ResponseBody']);
        if (is_numeric($this->context['__REAL_REDIRECT_CONTROLLER__'])) {
            if ($_isResponseBody) {
                if (is_numeric($interceptorResult)) {
                    die(MsgHelper::json($interceptorResult));
                } else {
                    die(MsgHelper::json($this->context['__REAL_REDIRECT_CONTROLLER__']));
                }
            } else {
                $this->_httpStatus($this->context['__REAL_REDIRECT_CONTROLLER__']);
            }
        } else {
            if ($_isResponseBody) {
                if (is_numeric($interceptorResult)) {
                    die(MsgHelper::json($interceptorResult));
                } else {
                    die(MsgHelper::json(10002));
                }
            } else {
                //此处url涉及到url传参
                //消除url关键字耦合
                $_returnParamKey = $this->context['__INTERCEPTORS__']['returnParamKey'];
                $_url = isset($_GET[$_returnParamKey]) ?
                    $_GET[$_returnParamKey] :
                    'http://' . $_SERVER['HTTP_HOST']
                    . $_SERVER['REQUEST_URI'];

                $_url = '?' . $_returnParamKey . '=' . urlencode($_url);

                header("Location: http://{$_SERVER['HTTP_HOST']}/"
                    . (isset($this->context['__VC__']) ? $this->context['__VC__'] . '/' : '')
                    . ltrim($this->context['__REAL_REDIRECT_CONTROLLER__'], '/')
                    . $_url . $this->context['__INTERCEPTORS__']['anchor']);
                exit;
            }
        }
    }

    /**
     * 初始化当前页面的路由信息
     */
    private function _setApplicationContext() {
        if (!isset($this->context['__PACKAGE__']) || $this->context['__PACKAGE__'] == '') {
            $this->context['__PACKAGE__'] = null;
        }

        $_hitFlag = false;
        $this->context['__LANGUAGE_ID__'] = 0;
        $_viewAbsolutePath = null;
        if (!is_null($this->context['__PACKAGE__'])) {
            foreach ($this->context['__ROUTE_CONFIG__']['languages'] as $_k => $_v) {
                if (strcasecmp($this->context['__PACKAGE__'], $_k) == 0) {
                    $this->context['__CORE_MAPPING_PATH__'] = $_k;

                    if (is_array($_v)) {
                        if (!isset($_v['id']) && !isset($_v['shared'])) {
                            throw new FormatException(MsgHelper::get('0x00000010'),
                                '0x00000010');
                        }
                        $this->context['__LANGUAGE_ID__'] = intval($_v['id']);

                        if (isset($_v['shared']) &&
                            isset($this->context['__ROUTE_CONFIG__']['languages'][$_v['shared']])
                        ) {
                            $this->context['__CORE_MAPPING_PATH__'] = $_v['shared'];
                        }
                        if (isset($_v['vue'])) {
                            $_viewAbsolutePath = $_v['vue'];
                        }
                    } else {
                        $this->context['__LANGUAGE_ID__'] = intval($_v);
                    }
                    $_hitFlag = true;
                    break;
                }
            }
        }
        //未匹配到语言，取默认第一项
        if (!$_hitFlag) {
            if (!is_null($this->context['__PACKAGE__'])) {
                array_unshift($this->context['__PATHS__'], $this->context['__PACKAGE__']);
            }
            reset($this->context['__ROUTE_CONFIG__']['languages']);

            $this->context['__PACKAGE__'] = key($this->context['__ROUTE_CONFIG__']['languages']);
            $this->context['__CORE_MAPPING_PATH__'] = $this->context['__PACKAGE__'];

            $_current = current($this->context['__ROUTE_CONFIG__']['languages']);
            if (isset($_current['shared'])) {
                $this->context['__CORE_MAPPING_PATH__'] = $_current['shared'];
                $this->context['__LANGUAGE_ID__'] = $_current['id'];
            } else {
                $this->context['__LANGUAGE_ID__'] = intval($_current);
            }
            if (isset($_current['vue'])) {
                $_viewAbsolutePath = $_current['vue'];
            }
            unset($_current);
        }

        //无任何参数，判定为首页
        if (count($this->context['__PATHS__']) == 0) {
            $this->context['__PATHS__'][0] = 'index';
        }
        $this->context['__HOMEPAGE__'] = false;
        if (strcasecmp('index', $this->context['__PATHS__'][0]) == 0) {
            /**
             * 为页面增加首页属性值
             */
            $this->context['__HOMEPAGE__'] = true;
        }


        //视图恒定则更改视图相关配置，不再以语言版本包为准
        $_lowerCI = strtolower($this->context['__PATHS__'][0]);
        $this->context['__PHP_SELF__'] = '';
        if (isset($this->context['__ROUTE_CONFIG__']['viewConstant'][$_lowerCI])) {
            $this->context['__PACKAGE__'] = $this->context['__CORE_MAPPING_PATH__'] = $this
                ->context['__ROUTE_CONFIG__']['viewConstant'][$_lowerCI];

            /**
             * 如果路由只有恒定目录，如（http://.../admin）则不排除，以响应路由做跳转
             */
            if (count($this->context['__PATHS__']) > 1) {
                array_shift($this->context['__PATHS__']);
            }
            //ViewConstant 视图恒定 用于判断__CI__是否命中为恒定目录 route.config配置
            $this->context['__VC__'] = $_lowerCI;
            $this->context['__PHP_SELF__'] = $_lowerCI . '/';
        }
        if (is_null($_viewAbsolutePath)) {
            $_viewAbsolutePath = $this->context['__PACKAGE__'];
        }
        //页面物理路径，除模板包之外直接取包名对应页面地址(隐藏真实路径)
        $this->context['__VIEW_ABSOLUTE_PATH__'] = ROOT_PATH
            . $this->context['__ROUTE_CONFIG__']['assetsDirectory']
            . DIRECTORY_SEPARATOR . $_viewAbsolutePath . DIRECTORY_SEPARATOR;
        //用于包含文件的视图绝对路径
        $this->context['__REQUIRE_ABSOLUTE_DIR__'] = $this->context['__VIEW_ABSOLUTE_PATH__'];

        $this->context['__ASSETS_PATH__'] .= $this->context['__PACKAGE__'] . '/';
        //==

        $this->context['__CONTROLLER_INDEX__'] = array_shift($this->context['__PATHS__']);
        $this->context['__CONTROLLER_MAPPING__'] = '/' . implode('/', $this->context['__PATHS__']);

        //当前完整的含参数请求地址
        $this->context['__VIEW__'] = null;
        if (false === $this->_isForwardFlag) {
            $this->context['__REQUEST_MAPPING__'] = null;
            $this->context['__PHP_SELF__'] .= $this->context['__CONTROLLER_INDEX__']
                . $this->context['__CONTROLLER_MAPPING__'];
            $this->context['__RC__'] = & $this->context['__ROUTE_CONFIG__'];
            $this->context['__CI__'] = & $this->context['__CONTROLLER_INDEX__'];
            $this->context['__CM__'] = & $this->context['__CONTROLLER_MAPPING__'];
            //解析类时带入的请求地址，一般用于分页，可以不含分页符 当开启“*”泛匹配时无作用，需要方法中手动设定
            $this->context['__RM__'] = & $this->context['__REQUEST_MAPPING__'];
            $this->context['__ASSETS__'] = & $this->context['__ASSETS_PATH__'];
            $this->context['__STATIC__'] = & $this->context['__ASSETS_STATIC_PATH__'];
            $this->context['__RAD__'] = & $this->context['__REQUIRE_ABSOLUTE_DIR__'];
        } else {
            $this->_isForwardFlag = false;
        }

        //将cacheable目录限制在当前package中
        $this->_cacheablePack .= $this->context['__PACKAGE__'] . '/';

        //类名中不能含有 "-"
        $this->context['__CORE_MAPPING_PATH__'] = ucfirst(str_replace('-', '', $this->context['__CORE_MAPPING_PATH__']));

        //获取拦截器配置文件
        $this->context['__INTERCEPTORS_CONFIG_PATH__'] = APP_PATH
            . $this->context['__CORE_MAPPING_PATH__'] . DIRECTORY_SEPARATOR . 'interceptors.config.php';

        $this->_getPageMethod();

//        die(var_dump($this->context));
    }

    /**
     * 控制层组件
     * 路由控制
     */
    private function _findController() {
        //$_controllerIndexCount = count($this->_controllers);
        $_flag = false;
        //logger::debug($this->_controllers);
        //logger::debug($this->_controllers);

        $this->context['__ACCEPT_INDEX__'] = 0;

        //controller中只有一个方法，直接取第一个
        //logger::debug($this->_controllers);
        //默认取第一项在未设定路由但是页面存在的情况下会出问题，此段代码只留作参考
        if (false !== $this->_controllers && !is_null($this->_controllers)) {
            if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']])) {
                $_flag = true;
            } else { //如果未直接匹配mapping，例如含其他参数的链接
                //var_dump($this->_controllers);
                //* $_tmpMapping = null;
                //* $_strlen = null;
                foreach ($this->_controllers as $_cm => $_v) {
                    if ($_cm != '/' && isset($_v[0]['__REGX__'])) {
                        $_regx = '/^' . $_v[0]['__REGX__'] . '$/U';
                        //url大小写不敏感 updated:2014-01-09
                        //未走路由的映射始终大小写敏感，关键在于映射磁盘文件
                        //windows服务器大小写不敏感不存在此问题
                        if (false === $this->context['__ROUTE_CONFIG__']['caseSensitive']) {
                            $_regx .= 'i';
                        }
                        if (preg_match($_regx, $this->context['__CONTROLLER_MAPPING__'])
                            //* && (!$_flag || ($_flag && $_v['__STRLEN__'] > $_strlen)) //更长更匹配
                            //* 由于controller已经进行过排序，这里匹配的就是更长的优先匹配 //*注释部分属于更长更匹配循环全部url映射
                            //* 这里执行依赖于 \Phoenix\ProxyFactory 355-379行排序部分
                        ) {
                            //* $_strlen = $_v['__STRLEN__'];
                            //* $_tmpMapping = $_cm;
                            $this->context['__CONTROLLER_MAPPING__'] = $_cm;
                            $_flag = true;
                            break;
                        }
                    }
                }
            }
        }

        //路由未找到进行泛匹配
        if (false === $_flag &&
            false !== ($this->_genericController = $this->_frameworkCache->get($this->_controllersGenericCacheId))// 泛路由存在且被加载
        ) {
            $this->_controllers = $this->_genericController;
            foreach ($this->_controllers as $_cm => $_v) {
                if ($_cm != '/' &&
                    isset($_v[0]['__REGX__']) &&
                    preg_match('/^' . $_v[0]['__REGX__'] . '$/iU',
                        '/' . $this->context['__CONTROLLER_INDEX__']
                        . rtrim($this->context['__CONTROLLER_MAPPING__'], '/'))
                ) {
                    array_unshift($this->context['__PATHS__'], $this->context['__CONTROLLER_INDEX__']);
                    $this->context['__CONTROLLER_INDEX__'] = '*';
                    $this->context['__CONTROLLER_MAPPING__'] = $_cm;
                    $_flag = true;
                    break;
                }
            }
        }

        //die(var_dump($_flag));
        //die(var_dump($this->_data['__CONTROLLER_MAPPING__']));

        $_re = false;
        if (false === $_flag) {
            $_re = $this->_findPageWithoutController();
        } else { //路由命中
            $_re = true;
            $_consumeFlag = true; //消费类型命中 Content-Type
            $_produceFlag = true; //生产类型命中 Accept
            $_methodFlag = false; //method命中

            $this->_updateMethod($this->context['__ACCEPT_INDEX__']);

            if (count($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]) > 1 ||
                isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                    [$this->context['__ACCEPT_INDEX__']]
                    [$this->context['__METHOD__']]['__ResponseBody'])) {
                $_consumeFlag = false;
                $_produceFlag = false;
                //是否设置了content-type
                $_contentType = 'text/html';
                if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] != '') {
                    $_contentType = $_SERVER['CONTENT_TYPE'];
                    if (($_cut = strpos($_contentType, ';')) !== false) {
                        $_contentType = substr($_contentType, 0, $_cut);
                    }
                }
                $_sortAccept = $this->_sortAccept();

                foreach ($this->_controllers[$this->context['__CONTROLLER_MAPPING__']] as $_key => $_v) {
                    if (isset($_v[$this->context['__METHOD__']]) &&
                        isset($_v[$this->context['__METHOD__']]['__ResponseBody'])) {
                        $_methodFlag = true;
                        if ($_key > 0) {
                            $this->_updateMethod($_key);
                        }
                    } else {
                        continue;
                    }

                    if (!isset($_v[$this->context['__METHOD__']]['__Consumes'])) {
                        $_consumeFlag = true;
                        if (($_produceFlag = $this->_analysisAccept($_key, $_sortAccept))) {
                            $this->context['__ACCEPT_INDEX__'] = $_key;
                            break;
                        }
                        continue;
                    }

                    $_consumes = $_v[$this->context['__METHOD__']]['__Consumes'];
                    if (is_array($_consumes)) {
                        foreach ($_consumes as $_cs) {
                            if (strcmp('*/*', $_cs) == 0
                                || strpos($_contentType, str_replace('*', '', $_cs)) !== false
                                || strpos($_contentType, substr($_cs, 0, strrpos($_cs, '/')) . '/*') !== false
                            ) {
                                $_consumeFlag = true;
                                if (($_produceFlag = $this->_analysisAccept($_key, $_sortAccept))) {
                                    $this->context['__ACCEPT_INDEX__'] = $_key;
                                    break 2;
                                }
                            }
                        }
                    } else if (strpos($_contentType, '*/*') !== false
                        || strpos($_contentType, str_replace('*', '', $_consumes)) !== false
                        || strpos($_contentType, substr($_consumes, 0, strrpos($_consumes, '/')) . '/*') !== false
                    ) {
                        $_consumeFlag = true;
                        if (($_produceFlag = $this->_analysisAccept($_key, $_sortAccept))) {
                            $this->context['__ACCEPT_INDEX__'] = $_key;
                            break;
                        }
                    }
                }
                unset($_sortAccept);
                $_sortAccept = null;
            } else if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']])) {
                $this->_updateMethod($this->context['__ACCEPT_INDEX__']);
                $_methodFlag = true;
            }

            if (false === $_methodFlag) {
                $this->_httpStatus(405);
            } else if (false === $_consumeFlag) {
                $this->_httpStatus(415);
            } else if (false === $_produceFlag) {
                $this->_httpStatus(406);
            }
        }

        return $_re;
    }

    /**
     * 分析可接受的文档类型，并输出文档与设定相符的文档，未命中返回406状态
     * @param $acceptIndex
     * @param $sortAccept
     * @return bool
     */
    private function _analysisAccept($acceptIndex, $sortAccept) {
        $_producesFlag = false;
        $this->context['__ACCEPT__'] = 'text/html';
        if (isset($this->context['__EXTENSIONS__'])) {
            switch ($this->context['__EXTENSIONS__']) {
                case 'json' :
                    $this->context['__ACCEPT__'] = 'application/json';
                    break;
                case 'xml' :
                    $this->context['__ACCEPT__'] = 'application/xml';
                    break;
            }
        }
        if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
            [$this->context['__METHOD__']]['__Produces'])) {
            $_produces = $this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
            [$this->context['__METHOD__']]['__Produces'];
            if (is_array($_produces)) {
                foreach ($sortAccept as $_k => $_v) {
                    foreach ($_produces as $_ps) {
                        if ($_k == '*/*') {
                            $this->context['__ACCEPT__'] = $_ps;
                            $_producesFlag = true;
                            break 2;
                        } else {
                            $_k = str_replace('*', '', $_k);
                            $_tmpPs = str_replace('*', '', $_ps);
                            if (strcasecmp($_k, $_tmpPs) == 0
                                || strpos($_k, $_tmpPs) !== false // $_k application/json, $_ap application/*
                                || strpos($_tmpPs, $_k) !== false //$_ap application/json, $_k application/*
                            ) {
                                $this->context['__ACCEPT__'] = $_ps;
                                $_producesFlag = true;
                                break 2;
                            }
                        }
                    }
                }
            } else if (strpos($_SERVER['HTTP_ACCEPT'], '*/*') !== false
                //$_produces : application/*
                || strpos($_SERVER['HTTP_ACCEPT'], str_replace('*', '', $_produces)) !== false
                //$http_accept application/* $produces application/json
                || strpos($_SERVER['HTTP_ACCEPT'], substr($_produces, 0, strrpos($_produces, '/')) . '/*') !== false
            ) {
                //文本类型的生产无所谓权重，只要存在即命中
                $this->context['__ACCEPT__'] = $_produces;
                $_producesFlag = true;
            }
        } else {
            $_producesFlag = true;
        }
        return $_producesFlag;
    }

    /**
     * 将可接受文档类型按照权重排序
     * @return bool
     */
    private function _sortAccept() {
        $_sortAccept = array();
        //"text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
        if (!isset($_SERVER['HTTP_ACCEPT']) || $_SERVER['HTTP_ACCEPT'] == '') {
            $_SERVER['HTTP_ACCEPT'] = '*/*';
            $_sortAccept = array(
                '*/*' => 10
            );
        } else {
            $_aryServerAccept = explode(',', $_SERVER['HTTP_ACCEPT']);
            foreach ($_aryServerAccept as $_sa) {
                if (strpos($_sa, ';') !== false) {
                    $_tmp = explode(';', $_sa);
                    $_sortAccept[$_tmp[0]] = str_replace('q=', '', $_tmp[1]) * 10;
                } else {
                    $_sortAccept[$_sa] = 10;
                }
            }
            unset($_aryServerAccept);
            $_aryServerAccept = null;

            arsort($_sortAccept);
        }
        return $_sortAccept;
    }

    /**
     * 将 http method 转发抹平
     * @param $acceptIndex
     */
    private function _updateMethod($acceptIndex) {
        //修改内存中转发的访问方法 POST => GET
        if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
                [$this->context['__METHOD__']])
            && !is_array($this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
            [$this->context['__METHOD__']])) {
            $_method =
                $this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
                [$this->context['__METHOD__']];

            $this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
            [$this->context['__METHOD__']]
                = $this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$acceptIndex]
            [$_method];
        }
    }

    /**
     * 不通过控制层组件找寻页面
     * @return bool
     * @throws FormatException
     */
    private function _findPageWithoutController() {
        $_tmpViewPath = $this->context['__VIEW_ABSOLUTE_PATH__']
            . $this->context['__CONTROLLER_INDEX__'];
        $_count = count($this->context['__PATHS__']) > 0;
        if ($_count) {
            $_tmpViewPath .= DIRECTORY_SEPARATOR
                . implode(DIRECTORY_SEPARATOR, $this->context['__PATHS__']);
        }
        if (!is_file($_tmpViewPath . $this->context['__TAGLIB__']) && $_count) {
            $this->context['__CONTROLLER_MAPPING__'] = null;
            $_paths = $this->context['__PATHS__'];
            $_tmpViewPath = $this->context['__VIEW_ABSOLUTE_PATH__']
                . $this->context['__CONTROLLER_INDEX__'];
            foreach ($this->context['__PATHS__'] as $_p) {
                array_pop($_paths);
                $_tmpViewPath .= DIRECTORY_SEPARATOR
                    . implode(DIRECTORY_SEPARATOR, $this->context['__PATHS__']);
                if (is_file($_tmpViewPath . $this->context['__TAGLIB__'])) { //逆向迭代找到最终存在的页面
                    //路由中参数已没有意义
                    $this->context['__CONTROLLER_MAPPING__'] = implode('/', $this->context['__PATHS__']);
                    break;
                }
            }
        }
        if (is_null($this->context['__CONTROLLER_MAPPING__'])) {
            //controller及页面均无抛出异常
            if (PX_DEBUG) {
                //抛出路由错误
                throw new FormatException(MsgHelper::get('0x00000006', $this->context['__PHP_SELF__']),
                    '0x00000006');
            } else {
                $this->_httpStatus(404); //其他一律转到404
            }
        } else {
            $this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
            [$this->context['__ACCEPT_INDEX__']]
            [$this->context['__METHOD__']]['__CONTROLLER_CLASS__'] = 404;
        }
        return false;
    }

    /**
     * 装配url参数
     */
    private function _pushUrlParameter() {
        //压入url参数 存入_data中
        //方法中$__Route设定了传值，则需要用名称对应的变量取值
        //如 exampleMethod($id, $__Route = '/{id}')第一个参数$id对应{id}
        //这个传值在页面中可以$this->id直接调用。对方法是局部，对当前页面是全局
        //(从参数中调用并不会从_data中删除，供当前页面环境中调用)
        $_mapping = $this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
        [$this->context['__ACCEPT_INDEX__']];
        if ($this->context['__ACCEPT_INDEX__'] > 0) {
            $_mapping = array_merge(
                $this->_controllers[$this->context['__CONTROLLER_MAPPING__']][0],
                $this->_controllers[$this->context['__CONTROLLER_MAPPING__']][$this->context['__ACCEPT_INDEX__']]
            );
        }
        if (isset($_mapping['__PARAMS__']) || isset($_mapping['__PRIORITYS__'])) {
            //删除url中不属于占位符的参数
            $_paths = $this->context['__PATHS__'];
            if (isset($_mapping['__CONSTANTS__'])) {//如果有常量存在
                foreach ($_mapping['__CONSTANTS__'] as $_constant) {
                    if (false !== ($_key = array_search($_constant, $_paths))) {
                        unset($_paths[$_key]);
                    }
                }
            }
            //正则参数优先匹配 __PRIORITYS__
            if (count($_paths) > 0 && isset($_mapping['__PRIORITYS__']) &&
                count($_mapping['__PRIORITYS__']) > 0) {
//			logger::debug($_paths);
//			logger::debug($_mapping['__PRIORITYS__']);
                foreach ($_paths as $_k => $_urlParam) {
                    foreach ($_mapping['__PRIORITYS__'] as $_priority => $_regx) {
                        if (strpos($_priority, '{') === false) {
                            if (preg_match('/^' . $_regx . '$/', $_urlParam)) {
                                $this->context['__PATH_VARIABLE__'][$_priority] = $_urlParam;
                                unset($_paths[$_k], $_mapping['__PRIORITYS__'][$_priority]);
                                break;
                            }
                        } else {
                            if (preg_match('/^' . $_regx . '$/', $_urlParam, $_match) &&
                                count($_match) > 1) {
                                $_tmp = explode('{', $_priority);
                                unset($_match[0], $_tmp[0]);
                                foreach ($_match as $_key => $_v) {
                                    if ($_v != '') {
                                        $this->context['__PATH_VARIABLE__'][$_tmp[$_key]] = $_v;
                                    }
                                }
                                unset($_paths[$_k], $_mapping['__PRIORITYS__'][$_priority]);
                                break;
                            }
                        }
                    }
                }
            }
            //匹配非正则变量 __PARAMS__
            if (count($_paths) > 0 &&
                count($_mapping['__PARAMS__']) > 0) {
                $_paths = array_slice($_paths, 0);
                foreach ($_paths as $_k => $_urlParam) {
                    if (isset($_mapping['__PARAMS__'][$_k])) {
                        $this->context['__PATH_VARIABLE__'][$_mapping['__PARAMS__'][$_k]] = $_urlParam;
                    }
                }
            }
            $_paths = $_mapping = null;
            unset($_paths, $_mapping);
        }
    }

    /**
     * 运行页面类
     */
    private function _controllerLoader() {
        $_clazz = $this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
        [$this->context['__ACCEPT_INDEX__']]
        [$this->context['__METHOD__']]['__CONTROLLER_CLASS__'];
        //die(var_dump($_clazz));
        if (!is_null($_clazz)) {
            $this->context['__CURRENT_PROPERTIES__'] =
                $this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']];

            if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__Exception'])) {

                set_exception_handler(array($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__Exception'], 'staticException'));

            }

//        logger::debug($this->_data['__CURRENT_PROPERTIES__']);
            if (isset($this->context['__CURRENT_PROPERTIES__']['__CACHEABLE__'])) {
                $this->context['__CACHEABLE__'] = $this->context['__CURRENT_PROPERTIES__']['__CACHEABLE__'];
                //连接一个cacheable目录
                $this->context['__CACHEABLE__'][0] = $this->_cacheablePack . $this->context['__CACHEABLE__'][0];

                if (!!($this->_currentBuffer = $this->_frameworkCache->mode($this->context['__CACHEABLE__'][2])
                    ->expires($this->context['__CACHEABLE__'][1])
                    ->get(
                        $this->context['__CACHEABLE__'][0]
                        . $this->_getCacheablePageName()
                    ))) {
                    return;
//                    echo $_pageCache;
//
//                    $this->_outDebug();
//
//                    exit;
                }
            }

            $_process = $this->context['__CURRENT_PROPERTIES__']['__PROCESS__'];
            if (is_null($this->context['__REQUEST_MAPPING__'])) {
                $this->context['__REQUEST_MAPPING__'] = $this->context['__CURRENT_PROPERTIES__']['__REQUEST_MAPPING__'];
            }
            $this->context['__VIEW__'] = $this->context['__CURRENT_PROPERTIES__']['__REQUEST_MAPPING__'];
            //die(var_dump($this->_data));
//            logger::debug($this->_data['__CURRENT_PROPERTIES__']);
            unset($this->context['__CURRENT_PROPERTIES__']['__CONTROLLER_CLASS__'],
                $this->context['__CURRENT_PROPERTIES__']['__PROCESS__'],
                $this->context['__CURRENT_PROPERTIES__']['__REQUEST_MAPPING__'],
                $this->context['__CURRENT_PROPERTIES__']['__CACHEABLE__'],
                $this->context['__CURRENT_PROPERTIES__']['__INTERCEPTOR__']
            );
            if (count($this->context['__CURRENT_PROPERTIES__']) > 0) {
                foreach ($this->context['__CURRENT_PROPERTIES__'] as $_k => $_v) {
                    if (isset($this->context[$_k])) {
                        $this->context['__CURRENT_PROPERTIES__'][$_k] = $this->context[$_k];
                    }
                }
            }

            //die(var_dump($this->_controllers[$this->_data['__CONTROLLER_MAPPING__']][$this->_data['__ACCEPT_INDEX__']]));
            //运行完毕
            //die(var_dump($this->_data['__VIEW__']));
            //重排参数索引 update:20140801
            $_currentProperties = array_values($this->context['__CURRENT_PROPERTIES__']);
            $this->context['__CURRENT_TRIGGER_CLASS__'] = $_clazz;
            $_completeData = self::$_instances->invoke($_clazz, $_process,
                $_currentProperties);
            if (isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__ResponseBody'])) {//Rest优先
                //如果设定了 __ResponseBody ：返回json数据，不再执行后面所有动作
                if (isset($_completeData['model'])) {
                    $this->_responseBody = $_completeData['model'];
                } else {
                    $this->_responseBody = $_completeData;
                }
            } else if (is_numeric($_completeData)) {
                $this->_httpStatus($_completeData);
            } else if (is_string($_completeData)) {//如果是字符串则直接返回到页面
                $this->_setViewOrRedirect($_completeData);
            } else if (is_null($_completeData)) {//如果是字符串则直接返回到页面
                $this->_setViewOrRedirect('null');
            } else {
                //die(var_dump($this->_data));
                //die(var_dump(isset($this->_injectMapping[$_clazz])));
                //如果返回数组
                if (is_array($_completeData)) {
                    if (isset($_completeData['view']) &&
                        is_string($_completeData['view'])) {
                        $this->_setViewOrRedirect($_completeData['view']);
                        unset($_completeData['view']);
                    }
                    /**
                     * 如果设置的为标准格式
                     * return array('view' => 'string', 'model' => array('key' => 'value'...));
                     * 或者为包含view的数组都可以正常解析
                     * 推荐返回视图及数据的时候使用标准格式，及含只有view model的数组，更语义化
                     */
                    if (isset($_completeData['model'])) {
                        $_completeData = $_completeData['model'];
                    }
                    //如果本类未注入框架数据则将返回的数组绑定至框架中统一返回给视图
                    //避免返回的数据已经注入过框架数据
                    //视图中始终能获取到框架变量
                    if (!isset($_completeData['__ROUTE_CONFIG__'])) {
                        $this->context = array_merge($this->context, $_completeData);
                    } else {
                        $this->context = $_completeData;
                    }
                }
                //die(var_dump($_completeData));
            }
            $_completeData = null;
            unset($_completeData);
            //die(var_dump($this->_data['__PHP_SELF__']));
            //$this->_data['__PHP_SELF__'] = trim($this->_data['__PHP_SELF__'], '/');
        }
    }

    /**
     * redirect跳转
     * @param type $view
     */
    private function _setViewOrRedirect($view) {
        if (strpos($view, ':') === false) {
            $this->context['__VIEW__'] = $view;
        } else {
            if (strpos($view, '301:') !== false) {
                header('HTTP/1.1 301 Moved Permanently');
                header("Location: http://{$_SERVER['HTTP_HOST']}" . str_replace('301:', '', $view));
            } else if (strpos($view, 'redirect:') !== false) {
                header("Location: http://{$_SERVER['HTTP_HOST']}" . str_replace('redirect:', '', $view));
            } else if (strpos($view, 'forward:') !== false) {
                $this->_isForwardFlag = true;
                Application::dispatcher(str_replace('forward:', '', $view));
            }
            exit;
        }
    }

    /**
     * 获取restful方法
     */
    private function _getPageMethod() {
        if (!isset($this->context['__METHOD__'])) {
            if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] == '') {
                $_SERVER['REQUEST_METHOD'] = 'GET';
            }
            $this->context['__METHOD__'] = $_SERVER['REQUEST_METHOD'];
        }
        if (strcasecmp('POST', $this->context['__METHOD__']) == 0) {
            if (isset($_POST['_method'])) {
                switch (strtolower($_POST['_method'])) {
                    case 'put' :
                        $this->context['__METHOD__'] = 'PUT';
                        break;
                    case 'delete' :
                        $this->context['__METHOD__'] = 'DELETE';
                        break;
                }
            }
        }
    }

    /**
     * 扫描控件
     * @param type $_path
     * @param null $_classList
     * @return type
     */
    private function _scanControllers($_path, & $_classList = null) {
        $_list = array(
            'class' => array(),
            'interface' => array()
        );
        $_path = rtrim($_path, DIRECTORY_SEPARATOR);
        $_handlerDir = DIRECTORY_SEPARATOR . $this->context['__ROUTE_CONFIG__']['handlerDirectory']
            . DIRECTORY_SEPARATOR;
        foreach (glob($_path . '/*') as $_item) {
            if (is_dir($_item) &&
                strpos($_item, $_handlerDir) === false) {
                $_list = array_merge_recursive($_list, $this->_scanControllers($_item, $_classList));
            } else if (strpos($_item, '.config.php') === false &&
                strpos($_item, '.php') !== false
            ) {
                //清除windows linux平台路径差异
                $_clazz = $this->context['__ROUTE_CONFIG__']['namespace']
                    . str_replace(array('/', APP_PATH, '.php', DIRECTORY_SEPARATOR),
                        array(DIRECTORY_SEPARATOR, '', '', '\\'), $_item);
                $_ft = filemtime($_item);
                //interface不在监控范围，毋需监控，监控类即可
                if (class_exists($_clazz) && (
                        is_null($_classList) ||
                        !isset($_classList[$_clazz]) ||
                        $_ft != $_classList[$_clazz]
                    )) {
                    $_aryInterface = self::$_instances->dissection($_clazz); //分析类并拿到映射
                    if (!is_null($_aryInterface) && count($_aryInterface) > 0) {
                        foreach ($_aryInterface as $_interface) {
                            $_list['interface'][$_interface] = $_clazz;
                        }
                    }
                    $_list['class'][$_clazz] = $_ft;
                }
            }
        }
        return $_list;
    }

    /**
     * 输出调试信息
     */
    private function _outDebug() {
        if (PX_DEBUG && !isset($this->_controllers[$this->context['__CONTROLLER_MAPPING__']]
                [$this->context['__ACCEPT_INDEX__']]
                [$this->context['__METHOD__']]['__ResponseBody'])) {
            $this->context['__E_MEMORY_USE__'] = memory_get_usage();
            $this->context['__E_RUNTIME__'] = microtime(true);
            $_total = 0;
            if (isset($this->context['__ROUTE_CONFIG__']['injects']['db'])) {
                $_clazz = $this->context['__ROUTE_CONFIG__']['injects']['db'];
                if (is_array($_clazz) && isset($_clazz['class'])) {//5.3.* has bug which if not use `is_array`
                    $_clazz = $_clazz['class'];
                }
                $_db = self::$_instances->inject($_clazz);
                $_total = $_db->total();
            }

            $_output = '<p style="'
                . (PX_DEBUG_DISPLAY ? '' : 'position:absolute;z-index:-1;top:-9999px;left:-9999px;')
                . 'text-align:center;font-size:12px;color:red;">执行时间：'
                . ($this->context['__E_RUNTIME__'] - $this->context['__S_RUNTIME__'])
                . '秒 查询数据库'
                . $_total
                . '次 内存使用：'
                . ($this->_convert($this->context['__E_MEMORY_USE__'])
                    . ' - ' . $this->_convert($this->context['__S_MEMORY_USE__'])
                    . ' = '
                    . $this->_convert($this->context['__E_MEMORY_USE__'] - $this->context['__S_MEMORY_USE__']))
                . ' 当前模式：developer</p>';

            echo $_output;
        }
    }

    /**
     * 获取页面缓存的命名后缀
     * @return type
     */
    private function _getCacheablePageName() {
        return count($this->context['__PATHS__']) > 0 ?
            '_' . implode('', $this->context['__PATHS__']) :
            '';
    }

    /**
     * 在模板生成中析构函数执行顺序不能达到要求
     * 改成直接插入数组
     * @param array $context
     * @param bool $isHookApi
     * @return mixed
     */
    public static function start(Array & $context = array('__PACKAGE__' => '',
        '__PATHS__' => array()), $isHookApi = false) {
        if (is_null(static::$_instances)) {
            static::$_instances = new ProxyFactory();
        }
        return static::$_instances->run($context, $isHookApi);
    }

}