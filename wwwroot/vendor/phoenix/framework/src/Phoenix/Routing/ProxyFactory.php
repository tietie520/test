<?php

namespace Phoenix\Routing;

if (!defined('IN_PX'))
    exit;

use ReflectionClass;
use Phoenix\Support\Helpers;
use Phoenix\Log\Log4p as logger;

/**
 * 代理类工厂
 *
 * @author Administrator
 * v 3.2.2 修正了方法中cacheable注入时的一个bug
 * 			修整了_parseCacheValue()方法，同时固定Controller的缓存始终使用磁盘介质
 * 			_getMappingRegx() 修正了排序以及 url根据正则、常量进行“/”的判定方式
 * 			类头支持 __ResponseBody() 以及 定义了类头同时方法中支持方法名映射的省略模式
 * 			update:2013-11-03
 * v 3.2.3 修正了类中定义了RequestMapping 方法中未定义，则类rm + 方法名（之前是三级类才触发）
 * 			_parseMappingUrl 末端加斜杠 _getMappingRegx将未带参数url也加入排序 并修正一个多参数无法排序bug
 * 			update:2013-12-05
 * v 3.2.5 排除php5.6版本新增魔术方法__debugInfo，增强方法级的注入，将注入类的初始化控制在方法级的粒度上
 *          注：会引发类进入代理模式
 * v 3.2.6 增加 __Consumes 消费者 __Produces 生产者 __Etag __LastModified __Expires 客户端缓存
 */
class ProxyFactory extends Controller {

    const VERSION = '3.2.9';

    private $_bundles = array();
    private $_currentLanguage = null;
    private $_proxyHandlers = array();
    //循环引用类映射
    private $_cyclicReferenceMapping = array();
    //循环引用需要延迟注入的类列表
    private $_cyclicReferenceLazyInjectList = array();
    //需要过滤掉的php魔术方法(魔术方法不能应用于控制层组件的分发)
    //包括框架的注入触发条件(设置了访问级别public的情况)
    private $_magicMethods = array('__construct' => 0, '__destruct' => 0,
        '__call' => 0, '__callStatic' => 0, '__get' => 0, '__set' => 0,
        '__isset' => 0, '__unset' => 0, '__sleep' => 0, '__wakeup' => 0,
        '__toString' => 0, '__set_state' => 0, '__clone' => 0,
        '__autoload' => 0, '__debugInfo' => 0,
        '__Bundle' => 0, '__Value' => 0, '__Inject' => 0, '__Singleton' => 0,
        '__Aspect' => 0, '__Transactional' => 0, '__Cacheable' => 0,
        '__CacheEvict' => 0, '__Interceptor' => 0, '__Method' => 0,
        '__Controller' => 0, '__Repository' => 0, '__Service' => 0, '__RPCService' => 0,
        '__Model' => 0, '__Named' => 0, '__Handler' => 0, '__Route' => 0,
        '__ResponseBody' => 0, '__RestController' => 0, '__InjectData' => 0,
        '__Consumes' => 0, '__Produces' => 0, '__Etag' => 0, '__LastModified' => 0,
        '__Expires' => 0, '__Exception' => 0);

    public final function & getBundles($key = null) {
        if (!is_null($key)) {
            return $this->_bundles[$key];
        }
        return $this->_bundles;
    }

    public final function & getData($key = null) {
        if (!is_null($key)) {
            return $this->context[$key];
        }
        return $this->context;
    }

    public final function getDataRouteConfig($type = 'injects', $key = null) {
        if (!is_null($key)) {
            return $this->context['__ROUTE_CONFIG__'][$type][$key];
        }
        return $this->context['__ROUTE_CONFIG__'][$type];
    }

    public final function getProxyHandlers($clazz) {
        return $this->_proxyHandlers[$clazz];
    }

    public final function hasSingleton($param) {
        return isset($this->_proxyHandlers['__SINGLETONS__'][$param]);
    }

    public final function getSingleton($param) {
        return $this->_proxyHandlers['__SINGLETONS__'][$param];
    }

    public final function setSingleton($param, $singletonMethod) {
        $this->_proxyHandlers['__SINGLETONS__'][$param] = $singletonMethod;
    }

    private function _singleMethod($reflectionClass, $method) {
        if ($reflectionClass->hasMethod($method) &&
            ($_m = $reflectionClass->getMethod($method)) &&
            ($_p = $_m->getParameters()) &&
            (strcmp($_p[0]->getName(), 'value') == 0) &&
            $_p[0]->isDefaultValueAvailable()) {
            return $_p[0]->getDefaultValue();
        }
        return null;
    }

    /**
     * 剖析类
     * @param $clazz
     * @return null
     */
    public final function dissection($clazz) {
        $_r = $this->_getReflectionClass($clazz);
        $_aryInterface = null;
        if (($_r->hasMethod('__Controller') || $_r->hasMethod('__RestController')) &&
            count($_methods = $_r->getMethods()) > 0) {//控制层组件
            preg_match('/^[^\\\\]+\\\\([^\\\\]+)\\\\.+/', $clazz, $_out);
            $this->_currentLanguage = $_out[1];
            $_classRequestMapping = null; //定义在头部的url
            $_aryClassMethods = null; //作用在整个类中的一组方法
            $_classHolder = array(
                '__Method' => null,
                '__Interceptor' => null,
                '__Consumes' => null,//消费
                '__Produces' => null,//生产
                '__Etag' => null,//无法提高性能，但会减轻网络传输压力
                '__LastModified' => null,
                '__Expires' => null,
                '__Exception' => null, //若实现非路由类异常会触发代理或反射，只在路由类中配置
                '__ApiPublish' => false, //api publish
            );
            $_cacheableValue = null;
            $_isUseClassCache = false;
            $_isResponseBody = false;
            $_isApiPublish = false;
            //20131205
            $_isClassRequestMappingFirstParam = false; //类中定义的rm第一项是否含有参数
            /**
             * 将__ResponseBody换成__RestController，将body只局限于方法中
             * 类中全局配置使用__RestController，类中所有方法全部返回responsebody
             * update:20140811
             */
            if ($_r->hasMethod('__RestController')) {
                $_isResponseBody = true;
                if ($_r->hasMethod('__ApiPublish')) {
                    $_classHolder['__ApiPublish'] = true;
                }
            }

            if ($_r->hasMethod('__Route') &&
                ($_m = $_r->getMethod('__Route')) &&
                ($_p = $_m->getParameters()) &&
                (strcmp($_p[0]->getName(), 'value') == 0) &&
                $_p[0]->isDefaultValueAvailable()) {
                $_classRequestMapping = $_p[0]->getDefaultValue();
                //20131205
                if (preg_match('/^\/[^\/]*{[^\/]+/', $_classRequestMapping)) {
                    $_isClassRequestMappingFirstParam = true;
                }
            }
            if ($_r->hasMethod('__Method') &&
                ($_m = $_r->getMethod('__Method')) &&
                ($_p = $_m->getParameters()) &&
                (strcmp($_p[0]->getName(), 'value') == 0) &&
                $_p[0]->isDefaultValueAvailable()) {
                $_classHolder['__Method'] = $_p[0]->getDefaultValue();
                if (is_array($_classHolder['__Method'])) {
                    $_aryClassMethods = $_classHolder['__Method'];
                    $_classHolder['__Method'] = array_shift($_aryClassMethods);
                    if (count($_aryClassMethods) == 0) {
                        $_aryClassMethods = null;
                    }
                }
            }
            $_classHolder['__Consumes'] = $this->_singleMethod($_r, '__Consumes');
            $_classHolder['__Produces'] = $this->_singleMethod($_r, '__Produces');
            if ($_r->hasMethod('__Etag')) {
                $_classHolder['__Etag'] = true;
            }
            if ($_r->hasMethod('__LastModified')) {
                $_classHolder['__LastModified'] = true;
            }
            $_classHolder['__Expires'] = $this->_singleMethod($_r, '__Expires');
            $_classHolder['__Exception'] = $this->_singleMethod($_r, '__Exception');
            //拦截器栈的id，拦截器只支持Controller的url拦截，如果想深入到方法中则使用aop
            $_classHolder['__Interceptor'] = $this->_singleMethod($_r, '__Interceptor');

            if ($_r->hasMethod('__Cacheable') &&
                ($_m = $_r->getMethod('__Cacheable'))
            ) {
                $_isUseClassCache = true;
                if (count($_p = $_m->getParameters()) > 0 &&
                    (strcmp($_p[0]->getName(), 'value') == 0) &&
                    $_p[0]->isDefaultValueAvailable()) {
                    $_cacheableValue = $_p[0]->getDefaultValue();
                }
            }
            foreach ($_methods as $_m) {//遍历方法
                if ($_m->isPublic() && ($_mn = $_m->getName()) &&
                    !isset($this->_magicMethods[$_mn])) {
                    //$_mn = $_m->getName();
                    $_parameters = array();
                    $_requestIndex = null;
                    $_requestMapping = null;
                    $_tmpRequestMapping = $_classRequestMapping;
                    $_method = is_null($_classHolder['__Method']) ? 'GET' : $_classHolder['__Method'];
                    $_parameters['__CONTROLLER_CLASS__'] = $clazz; //执行的类
                    $_parameters['__PROCESS__'] = $_mn; //执行的方法
                    $_parameters['__REQUEST_MAPPING__'] = null; //占位
                    if ($_isUseClassCache) {
                        $_parameters['__CACHEABLE__'] = null; //占位
                    }
                    if ($_isResponseBody) {
                        $_parameters['__ResponseBody'] = true; //占位
                    }
                    if (!is_null($_classHolder['__Consumes'])) {
                        $_parameters['__Consumes'] = $_classHolder['__Consumes']; //占位
                    }
                    if (!is_null($_classHolder['__Produces'])) {
                        $_parameters['__Produces'] = $_classHolder['__Produces']; //占位
                    }
                    if (!is_null($_classHolder['__Etag'])) {
                        $_parameters['__Etag'] = true; //占位
                    }
                    if (!is_null($_classHolder['__LastModified'])) {
                        $_parameters['__LastModified'] = true; //占位
                    }
                    if (!is_null($_classHolder['__Expires'])) {
                        $_parameters['__Expires'] = $_classHolder['__Expires']; //占位
                    }
                    if (!is_null($_classHolder['__Exception'])) {
                        $_parameters['__Exception'] = $_classHolder['__Exception']; //占位
                    }
                    if (!is_null($_classHolder['__Interceptor'])) {
                        $_parameters['__INTERCEPTOR__'] = $_classHolder['__Interceptor']; //当前类是否定义了私有拦截器
                    }
                    $_aryPdvs = null;
                    $_aryMethods = null;
                    foreach ($_m->getParameters() as $_p) { //遍历参数
                        $_pn = $_p->getName();
                        $_pdv = $_p->isDefaultValueAvailable() ?
                            $_p->getDefaultValue() : null;
                        switch ($_pn) {
                            case '__Route' :
                                if (!is_null($_pdv)) {
                                    if (is_array($_pdv)) {
                                        $_aryPdvs = $_pdv;
                                        $_pdv = array_shift($_aryPdvs);
                                        $this->_analysisAryRequestMapping($_aryPdvs);
                                    }

                                    /** 如果第一项为含有参数，则附加上方法名。支持省略写法
                                     *  主要是支持类头定义了url同时方法中rm采取方法名映射的省略模式
                                     *  2013-11-03
                                     *  2013-12-05 up 类头定义第一项为参数则不附加
                                     */
                                    if (false === $_isClassRequestMappingFirstParam &&
                                        preg_match('/^\/[^\/]*{[^\/]+/', $_pdv)) {
                                        $_pdv = '/' . $_mn . $_pdv;
                                    }

                                    $_tmpRequestMapping .= $_pdv;
                                }
                                $_parameters[$_pn] = true;
                                break;
                            case '__Method' :
                                if (is_array($_pdv)) {
                                    $_aryMethods = $_pdv;
                                    $_method = array_shift($_aryMethods);
                                    if (count($_aryMethods) == 0) {
                                        $_aryMethods = null;
                                    }
                                } else {
                                    $_method = $_pdv;
                                }
                                $_parameters[$_pn] = true;
                                break;
                            case '__ResponseBody' :
                                $_isApiPublish = true;
                            case '__Etag' :
                            case '__LastModified' :
                                $_parameters[$_pn] = true;
                                break;
                            case '__Interceptor' ://TODO 私有拦截栈名
                                if (!is_null($_pdv)) {
                                    $_parameters['__INTERCEPTOR__'] = $_pdv; //私有拦截器
                                }
                                $_parameters[$_pn] = true;
                                break;
                            case '__Cacheable' :
                                $_parameters['__CACHEABLE__'] = $this->_parseCacheValue($_pdv, $_mn);
                                $_parameters[$_pn] = true;
                                break;
                            default :
                                $_parameters[$_pn] = $_pdv;
                                break;
                        }
                    }
                    //全局设定了缓存但是方法中未指定
                    if ($_isUseClassCache && !isset($_parameters['__Cacheable'])) {
                        $_parameters['__CACHEABLE__'] = $this->_parseCacheValue($_cacheableValue);
                        if (is_null($_parameters['__CACHEABLE__'][0])) {
                            $_parameters['__CACHEABLE__'][0] = $_mn;
                        } else {
                            $_parameters['__CACHEABLE__'][0] .= $_mn;
                        }
                    }
                    //Controller缓存始终保存在磁盘---old
                    //应可缓存至内存缓存 20150317
//                    if (isset($_parameters['__CACHEABLE__'])) {
//                        $_parameters['__CACHEABLE__'][2] = 'file';
//                    }

                    /**
                     * 如果类及方法中未指定RequestMapping则触发(根据类及方法解析出url映射)
                     */
                    if (is_null($_tmpRequestMapping)) {
                        $this->_parseMappingUrl($_requestIndex,
                            $_requestMapping,
                            $this->_parseUrlWithClassName($clazz,
                                $this->context['__ROUTE_CONFIG__']['namespace']));
                        /**
                         * 如果类为二级结构(例：Site\xxx)则直接将方法名映射为路径
                         * 否则将方法名附加为路径
                         */
                        if (is_null($_requestIndex)) {
                            $_requestIndex = $_mn;
                        } else {
                            //将方法名映射到路径
                            $_requestMapping .= $_mn;
                        }
                    } else {
                        $this->_parseMappingUrl($_requestIndex,
                            $_requestMapping,
                            $_tmpRequestMapping);
                        //第一项是入参触发使用类名解析url映射
                        //如 /{id}/{page}，此时如果是三级类会将类名中心部分作为requestIndex，如 Chs_Member_Home
                        //方法附加到 [/class middle name]/method/{id}/{page}
                        //类中RequestMapping也为不定参数情况下
                        if (strpos($_requestIndex, '{') !== false) {
                            $_parseClassName = $this->_parseUrlWithClassName($clazz,
                                $this->context['__ROUTE_CONFIG__']['namespace']);
                            $_rm = $_ri = null;
                            $this->_parseMappingUrl($_ri, $_rm,
                                $_parseClassName);
                            if (is_null($_ri)) { //二级类
                                $_requestIndex = $_mn;
                                $_requestMapping = $_tmpRequestMapping;
                            } else { //三级以上类
                                $_requestIndex = $_ri;
                                //将方法名压入
                                $_requestMapping = $_rm . $_mn . $_tmpRequestMapping;
                            }
                        } else if (!isset($_parameters['__Route'])) {
                            //类中指定了RequestMapping，方法中未指定RequestMapping，则将方法名压入
                            $_requestMapping = rtrim($_requestMapping, '/') . '/' . $_mn;
                        }
                    }

                    $_parameters['__REQUEST_MAPPING__'] = $_requestIndex
                        . ($_requestMapping != '/' ?
                            preg_replace('/\/[^\/]*{[^\/]*/', '',
                                rtrim($_requestMapping, '/')) :
                            '');
//                    logger::debug($this->_scanControllers[$this->_currentLanguage]);
                    $_acceptKey = -1;
                    if (isset($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping])) {
                        $_acceptKey = count($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping]) - 1;
                    }

                    //查找已存在的方法，用于更新
                    $_hit = false;
                    if ($_acceptKey >= 0) {
                        foreach ($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping] as $_k => $_c) {
                            if (count($_c) > 0) {
                                foreach ($_c as $_v) {
                                    if (isset($_v['__PROCESS__'])
                                        && strcmp($_v['__PROCESS__'], $_parameters['__PROCESS__']) == 0) {
                                        $_acceptKey = $_k;
                                        $_hit = true;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    if ($_hit) {//update
                        $this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey][$_method] = $_parameters;
                    } else {//push
                        ++$_acceptKey;
                        $this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey][$_method] = $_parameters;
                    }

                    //==TODO if no effect ???
                    if (isset($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey])
                        && (!isset($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey][$_method])
                            || !is_array($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey][$_method]))
                    ) {
                        $this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey] = array_merge(
                            $this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey],
                            array($_method => $_parameters));
                    }
                    //==

                    //如果方法中没有设置__Method
                    if (!isset($_parameters['__Method'])) {
                        //如果类中也没有设置 Method 方法，则默认支持 GET,POST 访问
                        if (is_null($_classHolder['__Method'])) {
                            $_aryMethods = array('POST');
                        } else if (!is_null($_aryClassMethods)) {//如果类中设定的为多个访问方法
                            $_aryMethods = $_aryClassMethods;
                        }
                    }
                    if (!is_null($_aryMethods)) {
                        foreach ($_aryMethods as $_md) {//将同时设置的方法指向设定的第一项
                            if (!isset($this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey][$_md])) {
                                $this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping][$_acceptKey][$_md] = $_method;
                            }
                        }
                    }
                    //为url匹配及url入参提供支持
                    //执行本条的顺序应注意
                    //up 2013-12-30位置调整
                    $this->_getMappingRegx($_requestIndex, $_requestMapping, $_acceptKey);

                    //一组controller使用相同解析，浪费一点空间换取解析时间
                    if (!is_null($_aryPdvs)) {
                        //logger::debug($_aryPdvs);
                        foreach ($_aryPdvs as $_pdv) {
                            $_index = $_pdv['index'];
                            $_mapping = $_pdv['mapping'];

                            $this->_scanControllers[$this->_currentLanguage][$_index][$_mapping] = $this->_scanControllers[$this->_currentLanguage][$_requestIndex][$_requestMapping];
                            $_rm = $_index . ($_mapping != '/' ?
                                    preg_replace('/\/[^\/]*{[^\/]*/', '',
                                        rtrim($_mapping, '/')) :
                                    '');
                            foreach ($this->_scanControllers[$this->_currentLanguage][$_index][$_mapping][$_acceptKey] as $_k => $_v) {
                                if (is_array($_v) && isset($_v['__REQUEST_MAPPING__'])) {
                                    $this->_scanControllers[$this->_currentLanguage][$_index][$_mapping][$_acceptKey][$_k]['__REQUEST_MAPPING__'] = $_rm;
                                }
                            }
                            $_rm = null;
                            $this->_getMappingRegx($_index, $_mapping, $_acceptKey);
                            //logger::debug($_index);
                        }
                    }
                    if ($_classHolder['__ApiPublish'] || $_isApiPublish) {
                        $this->_apiPublish[$_requestIndex . $_requestMapping] = $_m->getDocComment() ? $_m->getDocComment() : '';
                        $_isApiPublish = false;
                    }
                }
            }
        }
//        else if ($_r->hasMethod('__Singleton') &&
//            count($_methods = $_r->getMethods()) > 0) {
//            foreach ($_methods as $_m) {//遍历方法
//                if ($_m->isPublic() && ($_mn = $_m->getName()) &&
//                    !isset($this->_magicMethods[$_mn])
//                ) {
//                    $this->_singletonMapping[$_mn] = $clazz;
//                }
//            }
//        }
        else if ($_r->hasMethod('__RPCService') &&
            count($_methods = $_r->getMethods()) > 0) {
            foreach ($_methods as $_m) {//遍历方法
                if ($_m->isPublic() && ($_mn = $_m->getName()) &&
                    !isset($this->_magicMethods[$_mn])
                ) {
                    $this->_rpcPublish[$clazz][$_mn] = $_m->getDocComment() ? $_m->getDocComment() : '';
                }
            }
        } else {
            if (!is_null($this->_arySysPlugins)) {
                foreach ($this->_arySysPlugins as $_plugin) {
                    $_plugin->branchDissectionController($_r, $clazz);
                }
            }
            if (!$_r->isAbstract()) {
                $_aryInterface = $_r->getInterfaceNames();

//                $_classSingleton = $_r->hasMethod('__Singleton');
//
//                if (count($_methods = $_r->getMethods()) > 0) {
//                    foreach ($_methods as $_m) {//遍历方法
//                        if ($_m->isPublic() && ($_mn = $_m->getName()) &&
//                            !isset($this->_magicMethods[$_mn])) {
//                            if ($_classSingleton) {
//                                $this->_singletonMapping[$_mn] = $clazz;
//                            }
//                            foreach ($_m->getParameters() as $_p) {//遍历参数
//                                $_pn = $_p->getName();
//                                switch ($_pn) {
//                                    case '__Singleton' :
//                                        $this->_singletonMapping[$_mn] = $clazz;
//                                        break;
//                                }
//                            }
//                        }
//                    }
//                }
            }
        }
        return $_aryInterface;
        //logger::debug($clazz);
        //logger::debug($this->_scanControllers[$this->_currentLanguage]);
    }

    /**
     * 对url映射解析出正则规则用于分发器匹配url以及压入参数
     * @param type $requestIndex
     * @param type $requestMapping
     */
    private function _getMappingRegx($requestIndex, $requestMapping, $key) {
        if ($key > 0) {
            return;
        }
        $_value = & $this->_scanControllers[$this->_currentLanguage][$requestIndex][$requestMapping][0];
        $_value['__STRLEN__'] = strlen(preg_replace('/{[^}]+}/', '*', $requestMapping));
        //避免多匹配将第一项的参数移植
        unset($_value['__REGX__'], $_value['__REGX__'],
            $_value['__PRIORITYS__'], $_value['__CONSTANTS__']);
        if (strpos($requestMapping, '{') !== false) {
            //文字长的优先匹配更容易命中指定路径文字的url，替代 substr_count($requestMapping, '/') 匹配
            $_mapping = explode('/', trim($requestMapping, '/'));
            $_params = null; //非正则参数
            $_priorities = null; //正则及含有字符的参数，优先匹配
            $_constants = null; //url中的常量
            $_quoteRgx = null;
            $_aryRegxs = null; //将url中除正则以外转义后的表达式数组
            foreach ($_mapping as $_regx) {
                if (strpos($_regx, '{') !== false) {
                    preg_match_all('/{([^}]+)}/U', $_regx, $_match);
                    $_quoteRgx = preg_replace('/{([^}]+)}/U', '*', $_regx);
                    foreach ($_match[1] as $_k => $_mt) {
                        if (strpos($_mt, ':') !== false) {
                            $_tmp = explode(':', $_mt);
                            $_match[1][$_k] = $_tmp[1];
                        } else {
                            $_match[1][$_k] = '[^\/]*';
                        }
                    }
                    $_quoteRgx = str_replace('\*', '*', preg_quote($_quoteRgx, '/'));
                    $_tmp = $_quoteRgx; //url验证中不需要()分组
                    foreach ($_match[1] as $_mt) {
                        //参数中含有字符串，需要分组取出
                        $_quoteRgx = preg_replace('/\*/', '(' . $_mt . ')', $_quoteRgx, 1);
                        $_tmp = preg_replace('/\*/', $_mt, $_tmp, 1);
                    }
                    $_quoteRgx = str_replace('\?', '?', $_quoteRgx); //支持?
                    $_ar = str_replace('\?', '?', $_tmp);
                    //var_dump($_pp);
                    //解析参数 完全匹配{}格式
                    if (preg_match('/^{([^}]+)}$/', $_regx, $_paramMatch)) {
                        if (strpos($_paramMatch[1], ':') !== false) {//如果含有正则
                            $_tmp = explode(':', $_paramMatch[1]);
                            $_priorities[$_tmp[0]] = $_tmp[1];
                            //如果正则中出现了 + 号，说明url与这个参数必须匹配
                            $_ar = '\/' . (strpos($_tmp[1], '+') === false ? '?' : '') . $_ar;
                        } else {
                            $_params[] = $_paramMatch[1];
                            $_ar = '\/?' . $_ar; //默认参数可以在url不存在
                        }
                    } else {//格式中含有特定字符如 show-{id}-{type}
                        $_tmp = preg_replace('/:[^}]+}/U', '}', $_regx);
                        preg_match_all('/{([^}]+)}/U', $_tmp, $_match);
                        $_tmp = '{' . implode('{', $_match[1]);
                        $_priorities[$_tmp] = $_quoteRgx;
                        $_ar = '\/' . $_ar; //定义了字符的正则必须匹配
                    }
                    $_aryRegxs[] = $_ar;
                } else {
                    $_constants[] = $_regx; //常量
                    $_aryRegxs[] = '\/' . $_regx; //常量必须匹配
                }
            }
//			$_mapping = '\/?' . implode('\/?', $_aryRegxs);
            $_mapping = implode('', $_aryRegxs);
            //logger::debug($_regx);
            $_value['__REGX__'] = $_mapping;
            $_value['__PARAMS__'] = $_params;
            if (!is_null($_priorities)) {
                $_value['__PRIORITYS__'] = $_priorities;
            }
            if (!is_null($_constants)) {
                $_value['__CONSTANTS__'] = $_constants;
            }
        }
        //$_params = $_aryRegxs = null;
        //排序
        //替代方案 uasort ，虽是php原生函数，但其他项目使用中有些问题，似乎并不适用于此排序，此函数未验证
        $_i = 0;
        if (count($this->_scanControllers[$this->_currentLanguage][$requestIndex]) > 0) {
            foreach ($this->_scanControllers[$this->_currentLanguage][$requestIndex] as $_map => $_v) {
                //对不包含url常量的mapping根据 __STRLEN__ 重新排序
                if (isset($_v[0]['__STRLEN__']) && strcmp($_map, $requestMapping) != 0) {
                    if ($_value['__STRLEN__'] > $_v[0]['__STRLEN__']) {
                        unset($this->_scanControllers[$this->_currentLanguage][$requestIndex][$requestMapping]);
                        array_splice($this->_scanControllers[$this->_currentLanguage][$requestIndex], $_i,
                            0, array($requestMapping => array($_value)));
                        $_tmp = null;
                        foreach ($this->_scanControllers[$this->_currentLanguage][$requestIndex] as $_k => $_v1) {
                            if ($_k === 0) {
                                $_tmp[$requestMapping][0] = $_value;
                                //logger::debug($requestMapping);
                            } else {
                                $_tmp[$_k] = $_v1;
                            }
                        }
                        if (!is_null($_tmp)) {
                            $this->_scanControllers[$this->_currentLanguage][$requestIndex] = $_tmp;
                        }
                        break;
                    }
                    $_i++;
                }
            }
        }
    }

    /**
     * 分析一组的 __Route 例如 $__Route = array('/...., /....')
     * 除第一项可以支持头部 __Route以及省略第一项(方法名映射)
     * 数组中其他项必须写入完整的url
     * @param type $aryPdvs 已排除第一项的url数组
     */
    private function _analysisAryRequestMapping(& $aryPdvs) {
        if (count($aryPdvs) == 0) {
            $aryPdvs = null;
            return;
        }
        $_tmp = array();
        foreach ($aryPdvs as $_pdv) {
            if (strlen($_pdv) > 1) {
                $_requestIndex = null;
                $_requestMapping = null;
                $this->_parseMappingUrl($_requestIndex,
                    $_requestMapping,
                    $_pdv);
                if (strpos($_requestIndex, '{') === false) {
                    $_tmp[] = array('index' => $_requestIndex,
                        'mapping' => $_requestMapping);
                }
            }
        }
        $aryPdvs = $_tmp;
        $_tmp = null;
        unset($_tmp);
    }

    /**
     * 分析aop类
     */
    public final function dissectionAspects() {
        foreach ($this->context['__ROUTE_CONFIG__']['aspectps'] as $_clazz) {
            $_r = $this->_getReflectionClass($_clazz);
            if ($_r->hasMethod('__Aspect') &&
                ($_aspect = $_r->getMethod('__Aspect')) &&
                ($_p = $_aspect->getParameters()) &&
                (strcmp($_p[0]->getName(), '__Pointcut') == 0) &&
                is_array($_pointcuts = $_p[0]->getDefaultValue()) &&
                count($_methods = $_r->getMethods()) > 1) {//aop切片
                //解析切入点
                foreach ($_pointcuts as $_pc => $_match) {
                    if (!isset($this->_aspectp[$_pc])) {
                        $this->_aspectp[$_pc]['class'] = $_clazz;
                        $_aryMatch = explode('.', $_match);
                        //匹配切入的类
                        $this->_aspectp[$_pc]['match'] = str_replace(array('\*\*', '\*'),
                            array('.*', '[^_]*'),
                            preg_quote($_aryMatch[0], '/'));
                        //匹配切入类中匹配的方法
                        if (isset($_aryMatch[1])) {
                            if (strpos($_aryMatch[1], ',') === false) {
                                $this->_aspectp[$_pc]['methods'] = str_replace('\*', '.*',
                                    preg_quote($_aryMatch[1], '/'));
                            } else {
                                $this->_aspectp[$_pc]['methods'] = explode(',', $_aryMatch[1]);
                                foreach ($this->_aspectp[$_pc]['methods'] as $_k => $_md) {
                                    $this->_aspectp[$_pc]['methods'][$_k] = str_replace('\*', '.*',
                                        preg_quote($_md, '/'));
                                }
                            }
                        } else {
                            $this->_aspectp[$_pc]['methods'] = '.*';
                        }
                    }
                }
                foreach ($_methods as $_m) {
                    if ($_m->isPublic() && ($_mn = $_m->getName()) &&
                        !isset($this->_magicMethods[$_mn])) {
                        foreach ($_m->getParameters() as $_p) {//遍历参数
                            if ($_p->isDefaultValueAvailable()) {
                                $_pn = $_p->getName();
                                $_pdv = $_p->getDefaultValue();
                                switch ($_pn) {
                                    case '__Before' :
                                        $this->_setAopMethod($_pdv, $_mn, 'before', 'joinPoint');
                                        break;
                                    case '__AfterReturning' :
                                        $this->_setAopMethod($_pdv, $_mn, 'afterReturning', 'returning');
                                        break;
                                    case '__AfterThrowing' :
                                        $this->_setAopMethod($_pdv, $_mn, 'afterThrowing', 'throwing');
                                        break;
                                    case '__After' :
                                        if (isset($this->_aspectp[$_pdv])) {
                                            $this->_aspectp[$_pdv]['after'] = $_mn;
                                        }
                                        break;
                                    case '__Around' :
                                        $this->_setAopMethod($_pdv, $_mn, 'around', 'proceedingJoinPoint');
                                        break;
                                }
                            }
                        }
                    }
                }
                //logger::debug($this->_aspectp);
            }
        }
    }

    /**
     * 向aspectp中添加参数
     * @param type $pdv
     * @param type $_mn
     * @param type $method
     * @param type $arg
     */
    private function _setAopMethod(& $pdv, $_mn, $method, $arg) {
        if (isset($pdv['pointcut']) && isset($this->_aspectp[$pdv['pointcut']])) {
            $this->_aspectp[$pdv['pointcut']][$method]['method'] = $_mn;
            if (isset($pdv[$arg])) {
                $this->_aspectp[$pdv['pointcut']][$method][$arg] = $pdv[$arg];
            }
        }
    }

    /**
     * 除代理免疫之外都需要通过调用此代理方法执行
     * 代理免疫直接返回的是类的实例，直接执行类的方法
     * @param type $clazz
     * @param type $process
     * @param type $args
     * @return type
     */
    public final function invoke($clazz, $process, & $args) {
        $this->_inject($clazz);

        if (count($this->_cyclicReferenceLazyInjectList) > 0) {
            foreach ($this->_cyclicReferenceLazyInjectList as $_clazz => $_inject) {
                foreach ($_inject as $_param => $_class) {
                    if (!is_null($this->_proxyHandlers[$_class]->currentInstances()) &&
                        !is_null($this->_proxyHandlers[$_clazz]->currentInstances())) {
                        $_tmp = $this->_proxyHandlers[$_clazz]->currentInstances();
                        if ($this->_proxyHandlers[$_class]->isAopProxy()) {
                            $_tmp->$_param = $this->_proxyHandlers[$_class];
                        } else {
                            $_tmp->$_param = $this->_proxyHandlers[$_class]->currentInstances();
                        }
                        unset($this->_cyclicReferenceLazyInjectList[$_clazz][$_param]);
                    }
                }
                if (count($this->_cyclicReferenceLazyInjectList[$_clazz]) == 0) {
                    unset($this->_cyclicReferenceLazyInjectList[$_clazz]);
                }
            }
        }

        if ($this->_proxyHandlers[$clazz]->isAopProxy()) {
            //logger::debug($clazz . ' - proxy');
            return $this->_proxyHandlers[$clazz]->$process($args);
        }
        return $this->_proxyHandlers[$clazz]->invoke($process, $args);
        //aop 后，环绕后
    }

    /**
     * 注入并返回代理类或类实例，可链式操作
     * @param type $clazz
     * @return type
     */
    public final function inject($clazz, $getInstances = false) {
        $this->_inject($clazz);

        if (!$getInstances && $this->_proxyHandlers[$clazz]->isAopProxy()) {
            //logger::debug($clazz . ' - proxy');
            return $this->_proxyHandlers[$clazz];
        }

        //logger::debug($clazz . ' - instances');
        return $this->_proxyHandlers[$clazz]->currentInstances();
    }

    /**
     * 可触发当前路由的注入，用于单元测试测试路由调用
     * @param $clazz
     * @param bool $getInstances
     * @return type
     */
    public final function trigger($clazz, $getInstances = false) {
        $this->context['__CURRENT_TRIGGER_CLASS__'] = $clazz;
        return $this->inject($clazz, $getInstances);
    }

    /**
     * 判断后注入，主要用于框架内部的注入
     * @param type $clazz
     * @return type
     */
    private function _inject($clazz) {
        $_cacheName = $this->_injectMappingCacheId . str_replace('\\', '/', $clazz);
        if (isset($this->_proxyHandlers[$clazz]) &&
            !is_null($this->_proxyHandlers[$clazz]->currentInstances())) {

            if ($this->_proxyHandlers[$clazz]->isAopProxy()) {
                return $this->_proxyHandlers[$clazz];
            }

            return $this->_proxyHandlers[$clazz]->currentInstances();
        }

        if ($this->_isUseInjectMapping &&
            !isset($this->_injectMapping[$clazz]) &&
            !is_null($this->_frameworkCache) &&
            false === ($this->_injectMapping[$clazz] = $this->_frameworkCache->get($_cacheName))) {
            unset($this->_injectMapping[$clazz]);
        }

        if (isset($this->_injectMapping[$clazz])) {
//            logger::debug($clazz . ' - instances');
            return $this->_injectByInjectMapping($clazz);
        } else {
            //logger::debug($clazz . ' - proxy');
            $_tmp = $this->_injectByReflection($clazz);
//            $this->_isInjectChange = true;
            //保存映射
            if (!isset($this->_injectMapping[$clazz])) {
                $this->_injectMapping[$clazz] = $this->_proxyHandlers[$clazz]->currentInjectMapping();
                $this->_frameworkCache->set($_cacheName, $this->_injectMapping[$clazz]);
                //logger::debug($clazz);
            }
            $this->_setAspectp($clazz);
            return $_tmp;
        }
    }

    /**
     * 注入执行aop时依赖的对象
     * @param type $clazz
     */
    private function _setAspectp($clazz) {
        if ($this->_proxyHandlers[$clazz]->checkJoinPointProperty('aspectp')) {
            $this->_proxyHandlers[$clazz]->setAspectpDependObj('aspectp', $this->_aspectp);
            //注入aop实例对象
            foreach ($this->_injectMapping[$clazz]['aspectMapping']['aspectp'] as $_pointcut => $_disabled) {
                $this->_proxyHandlers[$clazz]->setPointcutObject($_pointcut,
                    $this->_inject($this->_aspectp[$_pointcut]['class']));
            }
        }
        if ($this->_proxyHandlers[$clazz]->checkJoinPointProperty('transactional')) {
            $this->_proxyHandlers[$clazz]->setAspectpDependObj('transactional',
                $this->_inject('Phoenix\PXPDO\Decorator'));
        }
        if ($this->_proxyHandlers[$clazz]->checkJoinPointProperty('cacheable') ||
            $this->_proxyHandlers[$clazz]->checkJoinPointProperty('cacheEvict')) {
            $this->_proxyHandlers[$clazz]->setAspectpDependObj('cacheable',
                $this->_inject('Phoenix\Cache\Impl\CacheImpl'));
            //注入cacheable目录
            $this->_proxyHandlers[$clazz]->setCacheablePack($this->_cacheablePack);
        }
        if ($this->_proxyHandlers[$clazz]->checkJoinPointProperty('value') ||
            $this->_proxyHandlers[$clazz]->checkJoinPointProperty('inject') ||
            $this->_proxyHandlers[$clazz]->checkJoinPointProperty('importResource')) {
            $this->_proxyHandlers[$clazz]->setAspectpDependObj('proxyFactory', $this);
        }
    }

    /**
     * 分析cacheable设定
     * @param type $value
     * @param type $mn
     * @return null
     * update:2013-10-16
     */
    private function _parseCacheValue($value, $mn = null) {
        if (is_null($value)) { //未指定缓存id，则默认指定方法名为缓存名
            return array($mn, 0, null);
        } else if (is_numeric($value)) {//如果是纯数字
            return array($mn, $value, null);
        } else if (is_array($value)) { //数组
            $_pdv = $value;
            if (is_null($_pdv[0])) {
                $_pdv[0] = $mn;
            }
        } else { //纯字符串
            $_pdv = array($value, 0);
        }
        $_pdv[2] = null;
        if (strpos($_pdv[0], ':') !== false) { //如果指定了缓存介质
            $_tmp = explode(':', $_pdv[0]);
            $_pdv[0] = $_tmp[0]; //缓存id
            $_pdv[2] = $_tmp[1]; //介质
            unset($_tmp);
            $_tmp = null;
        }
        return $_pdv;
    }

    /**
     * 注入时分析aop
     * 方法级注入,从此处添加
     * @param type $clazz
     */
    private function _injectAnalysisAop($clazz) {
        //logger::debug($clazz);
        $_r = $this->_getReflectionClass($clazz);
        if ($_r->implementsInterface('Phoenix\Interceptor\IInterceptor') ||
            $_r->hasMethod('__Aspect')) {//拦截器及aop自身不执行任何aop行为
            return;
        }
        if (!is_null($this->_aspectp)) {//aop存在
            foreach ($this->_aspectp as $_pointcut => $_aop) {
                if ((strpos($_aop['match'], '*') !== false &&
                        preg_match('/' . $_aop['match'] . '/Ui',
                            $clazz)) || strcmp($_aop['match'], $clazz) == 0) {//aop类匹配
                    $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('aspectp', $_pointcut, 0);
                }
            }
        }
        //类全局事务，匹配此项本类所有public方法都在事务中执行
        if ($_r->hasMethod('__Transactional')) {
            $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('transactional', '__Transactional', 0);
        }
        //本类所有方法都缓存数据
        //格式：__Cacheable($value = 'id[:file]') or __Cacheable($value = array('id[:file]', 100[秒]))
        if (!$_r->hasMethod('__Controller') && //Controller指定缓存则缓存整个页面或最终输出的数据
            $_r->hasMethod('__Cacheable') &&
            ($_m = $_r->getMethod('__Cacheable'))
        ) {
            if (count($_p = $_m->getParameters()) > 0 &&
                (strcmp($_p[0]->getName(), 'value') == 0) &&
                $_p[0]->isDefaultValueAvailable()) {
                $_classCacheableValue = $_p[0]->getDefaultValue();
            }
            //update:2013-10-16
            $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('cacheable',
                '__Cacheable', $this->_parseCacheValue($_classCacheableValue));
        }
        //本类所有方法执行则触发删除指定缓存
        //格式：__CacheEvict($value = 'id[:file]') or __CacheEvict($value = array('id[:file]', 'id2'))
        if ($_r->hasMethod('__CacheEvict') && ($_m = $_r->getMethod('__CacheEvict') &&
                count($_p = $_m->getParameters()) > 0 &&
                (strcmp($_p[0]->getName(), 'value') == 0) &&
                $_p[0]->isDefaultValueAvailable())
        ) {
            $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('cacheEvict',
                '__CacheEvict', $_p[0]->getDefaultValue());
        }

//        $_classSingleton = $_r->hasMethod('__Singleton');

        if (count($_methods = $_r->getMethods()) > 0) {
            foreach ($_methods as $_m) {//遍历方法
                if ($_m->isPublic() && ($_mn = $_m->getName()) &&
                    !isset($this->_magicMethods[$_mn])) {
//                    if ($_classSingleton) {
//                        $this->_singletonMapping[$_mn] = $clazz;
//                    }
                    foreach ($_m->getParameters() as $_p) {//遍历参数
                        $_pn = $_p->getName();
                        $_pdv = $_p->isDefaultValueAvailable() ?
                            $_p->getDefaultValue() : null;
                        switch ($_pn) {
                            case '__Transactional' :
                                if (!$this->_proxyHandlers[$clazz]->checkJoinPointProperty('transactional',
                                    '__Transactional')) {
                                    $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('transactional',
                                        $_mn, 0);
                                }
                                break;
                            case '__Cacheable' :
                                //定义在方法上的缓存级别最高
                                if (!$_r->hasMethod('__Controller')) {
                                    $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('cacheable',
                                        $_mn, $this->_parseCacheValue($_pdv, $_mn));
                                }
                                break;
                            case '__CacheEvict' :
                                //定义在方法上的删除缓存级别最高
                                $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('cacheEvict',
                                    $_mn, $_pdv);
                                break;
//                            case '__Singleton' :
//                                $this->_singletonMapping[$_mn] = $clazz;
//                                break;
                            /**
                             * 方法级的类及资源注入会在方法调用时才实例化或加载，虽然有延迟加载的功能
                             * 但导致整个当前类使用代理，因此性能上的损耗各有取舍
                             */
                            case '__Value' :
                                $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('value',
                                    $_mn, $this->_clearDollarSign2Json($_pdv));
                                break;
                            case '__Inject' :
                                if (!is_null($_pdv)) {
                                    $_fixedPdv = array();
                                    $_singletons = array();
                                    if (is_array($_pdv)) {
                                        foreach ($_pdv as $_paramName => $_className) {
                                            if (is_numeric($_paramName)) {
                                                $_className = ltrim($_className, '$');
                                                $_paramName = $_className;
                                            } else {
                                                $_paramName = ltrim($_paramName, '$');
                                            }
                                            list($_className, $_tmpRef, $_hitClass, $_singletonMethod) =
                                                $this->_jitInspection($_paramName, $_className, 'string');
                                            if (!is_null($_className)) {
                                                $_singletonMethod = $this->_jitCheckSingletonParameter($_paramName,
                                                    $_className, $_tmpRef, $_hitClass, $_singletonMethod);
                                                $_fixedPdv[$_paramName] = $_className;

                                                if ($_singletonMethod) {
                                                    $_singletons[$_paramName] = 0;
                                                }
                                            }
                                        }
                                    } else {
                                        $_pdv = ltrim($_pdv, '$');
                                        list($_className, $_tmpRef, $_hitClass, $_singletonMethod) =
                                            $this->_jitInspection($_pdv, $_pdv);
                                        if (!is_null($_className)) {
                                            $_singletonMethod = $this->_jitCheckSingletonParameter($_pdv,
                                                $_className, $_tmpRef, $_hitClass, $_singletonMethod);
                                            $_fixedPdv[$_pdv] = $_className;

                                            if ($_singletonMethod) {
                                                $_singletons[$_pdv] = 0;
                                            }
                                        }
                                    }
                                }
                                $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('inject',
                                    $_mn, Helpers::jsonEncode($_fixedPdv));
                                $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('singleton',
                                    $_mn, Helpers::jsonEncode($_singletons));
//                                logger::d($_fixedPdv);
                                break;
                            case '__Bundle' :
                                $this->_proxyHandlers[$clazz]->setJoinPointMethodsMapping('importResource',
                                    $_mn, $this->_clearDollarSign2Json($_pdv));
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * 将字符串或数组中字符串的首字母$符号过滤，并返回为一个json字符串
     * @param type $value
     * @return type
     */
    private function _clearDollarSign2Json($value) {
        if (is_array($value)) {
            $_newValue = array();
            foreach ($value as $_k => $_v) {
                if (!is_numeric($_k)) {
                    $_k = ltrim($_k, '$');
                }
                $_v = ltrim($_v, '$');
                $_newValue[$_k] = $_v;
            }
            return Helpers::jsonEncode($_newValue);
        }
        return Helpers::jsonEncode(ltrim($value, '$'));
    }

    /**
     * 运行时检查
     * @param $paramName
     * @param $className
     * @param string $type
     * @return mixed
     */
    private function _jitInspection($paramName, $className = null, $type = 'reflection') {
        $_className = null;
        $_tmpRef = null;//运行时检查
        //如果存在于route.config.php配置项【injects】中，则始终返回配置类
        //面向接口编程优先 update:2015-08-05，将优先级调整到最上
        $_hitClass = false;
        $_singletonMethod = false;
        if (isset($this->context['__ROUTE_CONFIG__']['singletons'][$paramName])) {
            $_hitClass = true;
            $_singletonMethod = true;
            $_className = $this->context['__ROUTE_CONFIG__']['singletons'][$paramName];
        } else if (!is_null($this->_singletonMapping) &&
            isset($this->_singletonMapping[$paramName])) {
            $_hitClass = true;
            $_singletonMethod = true;
            $_className = $this->_singletonMapping[$paramName];
//        } else if (!is_null($_className = $_injects->getClass())) {
        } else if (!is_null($className)) {
            $_className = $type == 'reflection' ? $className->getName() : $className;

            if (isset($this->context['__ROUTE_CONFIG__']['injects'][$_className])) {
                if (is_string($this->context['__ROUTE_CONFIG__']['injects'][$_className])) {
                    $_hitClass = true;
                    $_className = $this->context['__ROUTE_CONFIG__']['injects'][$_className];
                } else if (is_array($this->context['__ROUTE_CONFIG__']['injects'][$_className]) &&
                    isset($this->context['__ROUTE_CONFIG__']['injects'][$_className]['class'])) {
                    $_hitClass = true;
                    $_className = $this->context['__ROUTE_CONFIG__']['injects'][$_className]['class'];
                }
            } else {
                $_tmpRef = new ReflectionClass($_className);
                $_injectId = lcfirst($_tmpRef->getShortName());//5.3+
                //如果 route 配置了指定实现，则直接读取，面向接口编程,配置可以改变接口行为
                //默认 injectId 为短接口(类，抽象类)名首字母小写
                //或者写全命名空间，接口及抽象类都匹配
                //配置优先
                if (isset($this->context['__ROUTE_CONFIG__']['injects'][$_injectId])) {
//                    $_className = $_injectId;
                    if (isset($this->context['__ROUTE_CONFIG__']['injects'][$_injectId])) {
                        if (is_string($this->context['__ROUTE_CONFIG__']['injects'][$_injectId])) {
                            $_hitClass = true;
                            $_className = $this->context['__ROUTE_CONFIG__']['injects'][$_injectId];
                        } else if (is_array($this->context['__ROUTE_CONFIG__']['injects'][$_injectId]) &&
                            isset($this->context['__ROUTE_CONFIG__']['injects'][$_injectId]['class'])) {
                            $_hitClass = true;
                            $_className = $this->context['__ROUTE_CONFIG__']['injects'][$_injectId]['class'];
                        }
                    }
                }

                if (false === $_hitClass && $_tmpRef->isInterface()) {//如果为接口
                    switch ($this->context['__ROUTE_CONFIG__']['interfaceMode']) {
                        //严格模式：接口所在包\Impl\接口名Impl
                        case 'strict' :
                            $_hitClass = true;
                            $_className = str_replace($_tmpRef->getShortName(), '', $_className)
                                . 'Impl\\' . $_tmpRef->getShortName() . 'Impl';
//                              die(var_dump('0 ' . $_className));
                            break;
                        //更灵活auto模式：框架扫描类之后将接口与实现配对
                        case 'auto' :
                            $_hitClass = true;
                            $_className = $this->_interfaceMapping[$_className];
//                              die(var_dump($_className));
                            break;
                    }
                }
            }
        }
        if (false === $_hitClass && isset($this->context['__ROUTE_CONFIG__']['injects'][$paramName])) {
            //变量名作为id进行匹配
            if (is_string($this->context['__ROUTE_CONFIG__']['injects'][$paramName])) {
                $_className = $this->context['__ROUTE_CONFIG__']['injects'][$paramName];
            } else if (is_array($this->context['__ROUTE_CONFIG__']['injects'][$paramName]) &&
                isset($this->context['__ROUTE_CONFIG__']['injects'][$paramName]['class'])) {
                $_className = $this->context['__ROUTE_CONFIG__']['injects']
                [$paramName]['class'];
            }
        }
        return array($_className, $_tmpRef, $_hitClass, $_singletonMethod);
    }

    /**
     * 运行时检查是否标明为$__Singleton单例方法
     * @param $_paramName
     * @param $_className
     * @param $_tmpRef
     * @param $_hitClass
     * @param $_singletonMethod
     * @return bool
     */
    private function _jitCheckSingletonParameter($_paramName, $_className, $_tmpRef,
                                                 $_hitClass, $_singletonMethod) {
        if ($_hitClass) {
            $_tmpRef = new ReflectionClass($_className);
        }
        if (!is_null($_tmpRef) && $_tmpRef->hasMethod($_paramName)) {//JIT
            $_singletonMethod = true;
            if ($_tmpRef->hasMethod('__Singleton')) {
                $_methods = $_tmpRef->getMethods();
                foreach ($_methods as $_m) {//遍历方法
                    if ($_m->isPublic() && ($_mn = $_m->getName()) &&
                        !isset($this->_magicMethods[$_mn])) {
                        $this->_singletonMapping[$_mn] = $_className;
                    }
                }
            } else {
                foreach ($_tmpRef->getMethod($_paramName)->getParameters() as $_p) {//遍历参数
                    $_pn = $_p->getName();
                    switch ($_pn) {
                        case '__Singleton' :
                            $this->_singletonMapping[$_paramName] = $_className;
                            break;
                    }
                }
            }
        }
        if (!is_null($_tmpRef)) {
            unset($_tmpRef, $_injectId);
            $_tmpRef = null;
        }
        return $_singletonMethod;
    }

    /**
     * 反射注入
     * 类级别注入,由此处添加
     * @param type $clazz
     * @return type
     */
    private function _injectByReflection($clazz) {
        //$this->_injectReviseClassNamed($clazz);
        $this->_injectAnalysisAop($clazz); //分析aop选项，aop不分析别名
//        logger::e($clazz);
        $_r = $this->_getReflectionClass($clazz);
        if (($_r->hasMethod('__Handler') || $_r->hasMethod('__RPCService')) &&
            $_r->hasMethod('__Interceptor') &&
            ($_m = $_r->getMethod('__Interceptor')) &&
            ($_p = $_m->getParameters()) &&
            (strcmp($_p[0]->getName(), 'value') == 0) &&
            $_p[0]->isDefaultValueAvailable()) {
            $this->_proxyHandlers[$clazz]->setHandlerPrivateInterceptor($_p[0]->getDefaultValue());
        }
        if ($_r->hasMethod('__Bundle') &&
            ($_m = $_r->getMethod('__Bundle')) &&
            count($_p = $_m->getParameters()) > 0
        ) {//外部资源
            $this->_proxyHandlers[$clazz]->setProxyImportResource($_p, $this->_bundles);
        }
        if ($_r->hasMethod('__Value') &&
            ($_m = $_r->getMethod('__Value')) &&
            count($_p = $_m->getParameters()) > 0
        ) {//取出已经导入的资源，变量名匹配
            $this->_proxyHandlers[$clazz]->getProxyUtil($_p, $this->context, $this->_bundles);
        }
        if ($_r->hasMethod('__Inject') &&
            ($_m = $_r->getMethod('__Inject')) &&
            count($_p = $_m->getParameters()) > 0
        ) {//持久、服务、可装载组件
            foreach ($_p as $_injects) {
                $_paramName = $_injects->getName(); //变量名

                list($_className, $_tmpRef, $_hitClass, $_singletonMethod) =
                    $this->_jitInspection($_paramName, $_injects->getClass());

                //不能直接判断class name 否则会丢失不同变量调用相同类的注入
                //已经是单例
                if (!is_null($_className)) {
                    $_singletonMethod = $this->_jitCheckSingletonParameter($_paramName,
                        $_className, $_tmpRef, $_hitClass, $_singletonMethod);

                    if (!isset($this->_proxyHandlers[$_paramName])) {
                        //logger::debug($_parameName);
                        //递归直至所有的类注入并实例化
                        //这里注入无返回值，注入时如果注入的类为代理免疫，直接注入类实例
                        //(触发点为__inject__)
                        $this->_cyclicReferenceMapping[$clazz][$_className] = 0;
                        //cyclic reference is found
                        if (isset($this->_cyclicReferenceMapping[$_className][$clazz])) {
                            $this->_cyclicReferenceLazyInjectList[$clazz][$_paramName] = $_className;
                        } else {
                            $this->_inject($_className);
                        }
                    }
                    $this->_proxyHandlers[$clazz]->setProxyInject($_className, $_paramName);

                    if ($_singletonMethod) {
                        $this->_proxyHandlers[$clazz]->setSingletonInject($_paramName);
                        $this->_frameworkCache->set($this->_singletonMappingCacheId, $this->_singletonMapping);
                    }
                }

            }
        }
//        if ($_singletonMethod) {
//            $this->_proxyHandlers[$clazz]->setSingletonInject($_paramName);
//        }
//        logger::debug($this->_singletonMapping);

        //注入自身及响应递归资源
        $this->_proxyHandlers[$clazz]->inject($this->_proxyHandlers, $this->context, $this->_bundles);
//        if (!is_null($this->_singletonMapping)) {
//            $this->_frameworkCache->set($this->_singletonMappingCacheId, $this->_singletonMapping);
//        }

        //返回(如果含有aop切入点则返回代理类)
        if ($this->_proxyHandlers[$clazz]->isAopProxy()) {
            return $this->_proxyHandlers[$clazz];
        }
        return $this->_proxyHandlers[$clazz]->currentInstances();
    }

    /**
     * 映射注入
     * @param type $clazz
     * @return type
     */
    private function _injectByInjectMapping($clazz) {
        //$this->_injectReviseClassNamed($clazz);
//        logger::debug($clazz);
//        if (!isset($this->_proxyHandlers[$clazz])) {//remove 20150804

        $this->_proxyHandlers[$clazz] = new ProxyHandler($clazz, $this->_injectMapping[$clazz]);

//        }
        $this->_setAspectp($clazz);
//		logger::debug($clazz);
//		logger::debug($this->_injectMapping[$clazz]);
        //导入资源
        if (isset($this->_injectMapping[$clazz]['bundles']) &&
            count($this->_injectMapping[$clazz]['bundles']) > 0) {
            foreach ($this->_injectMapping[$clazz]['bundles'] as $_k => $_v) {
                switch ($_v) {
                    case '@data' :
                        break;
                    case '@import' :
                        $this->_proxyHandlers[$clazz]->importVendorsResource($_k);
                        break;
                    case '@config' :
                        $_v = $this->context['__ROUTE_CONFIG__']['bundles'][$_k];
                    default : //ImportResource
                        if (!isset($this->_bundles[$_v])) {
                            $this->_bundles[$_v] = require ROOT_PATH . $_v;
                        }
                        break;
                }
            }
        }
        if (isset($this->_injectMapping[$clazz]['injects']) &&
            count($this->_injectMapping[$clazz]['injects']) > 0) {
            foreach ($this->_injectMapping[$clazz]['injects'] as $_paramName => $_className) {
                if (!isset($this->_proxyHandlers[$_paramName])) {
//                    logger::debug($_className);
                    $this->_cyclicReferenceMapping[$clazz][$_className] = 0;
                    //cyclic reference is found
                    if (isset($this->_cyclicReferenceMapping[$_className][$clazz])) {
                        $this->_cyclicReferenceLazyInjectList[$clazz][$_paramName] = $_className;
                    } else {
                        $this->_inject($_className);
                    }
                }
            }
        }
        //注入自身及响应递归资源
        $this->_proxyHandlers[$clazz]->inject($this->_proxyHandlers, $this->context, $this->_bundles);
        //var_dump($this->_proxyHandlers[$clazz]);
        //返回(代理类或者代理免疫的类实例)
        //这里返回的是最初调用的类
        if ($this->_proxyHandlers[$clazz]->isAopProxy()) {
            return $this->_proxyHandlers[$clazz];
        }
        return $this->_proxyHandlers[$clazz]->currentInstances();
    }

    /**
     * 得到映射url的值
     * @param type $_tmpRequestMappingIndex
     * @param type $_tmpRequestMapping
     * @param type $url
     */
    private function _parseMappingUrl(& $_tmpRequestMappingIndex, & $_tmpRequestMapping, $url) {
        if (is_null($url)) {
            $_tmpRequestMappingIndex = null;
            $_tmpRequestMapping = '/';
        } else {
            $url = trim($url, '/');
            if (substr_count($url, '/') > 0) {
                $_pos = strpos($url, '/');
                $_tmpRequestMappingIndex = substr($url, 0, $_pos);
                $_tmpRequestMapping = substr($url, $_pos);
            } else {
                $_tmpRequestMappingIndex = $url;
                $_tmpRequestMapping = '/';
            }
        }
    }

    /**
     * 获取类反射
     * @param type $clazz
     * @return type
     */
    private function _getReflectionClass($clazz) {
        if (!isset($this->_proxyHandlers[$clazz])) {
            $this->_proxyHandlers[$clazz] = new ProxyHandler($clazz);
        }
        return $this->_proxyHandlers[$clazz]->currentReflectionClass();
    }

}
