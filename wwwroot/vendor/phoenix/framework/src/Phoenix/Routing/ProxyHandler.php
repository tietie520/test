<?php

namespace Phoenix\Routing;

if (!defined('IN_PX'))
    exit;

use ReflectionClass;
use Exception;
use Phoenix\Log\Log4p as logger;

/**
 * 代理类
 *
 * @author Administrator
 */
class ProxyHandler {

    const VERSION = '3.2.3';

    private $_instances = null;
    private $_reflectionClass = null;
    private $_isUseReflection = true; //是否使用反射
    private $_clazz = null;
    private $_injectMapping = null; //自身资源映射
    //__construct注入完毕以及对象注入时重复注入的临时协调者
    private $_tmpInjectCoordinator = null;
    //本类是否还有切入点，如果为true，则注入及返回的为代理类，否则直接返回实例
    private $_hasJoinPoint = false;
    private $_aspectMapping = null;
    private $_aspectCache = null;
    private $_cacheablePack = null;
    private $_aspectDb = null;
    private $_pointcutObj = null; //aop类
    private $_currentMethod = null; //当前方法名
    private $_arguments = null; //当前方法参数
    private $_proceedResult = null;
    private $_data = null;
    private $_proxyFactory = null; //proxy factory
    private $_aryTmpProxyUtil = null;

    public final function __construct($clazz, & $injectMapping = null) {
        $this->_clazz = $clazz;
        if (!is_null($injectMapping)) {
            $this->_injectMapping = & $injectMapping;
//            logger::debug($injectMapping);
            $this->_isUseReflection = false;
            if (isset($this->_injectMapping['aspectMapping'])) {
                $this->_hasJoinPoint = true;
            }
        } else {
            $this->_reflectionClass = new ReflectionClass($clazz);
        }
    }

    /**
     * 将aop映射加入注入索引中
     * @param type $type
     * @param type $key
     * @param type $value
     * @return type
     */
    public final function setJoinPointMethodsMapping($type, $key, $value) {
        $this->_hasJoinPoint = true;
        //aop切入点的方法映射
        if (!isset($this->_injectMapping['aspectMapping'][$type][$key])) {
            $this->_injectMapping['aspectMapping'][$type][$key] = $value;
        }
        return $this->_hasJoinPoint;
    }

    /**
     * 测试aop属性
     * @param type $type
     * @param type $key
     * @return type
     */
    public final function checkJoinPointProperty($type, $key = null) {
        if (is_null($key)) {
            return isset($this->_injectMapping['aspectMapping'][$type]);
        }
        return isset($this->_injectMapping['aspectMapping'][$type][$key]);
    }

    /**
     * 将aop依赖对象导入
     * @param type $aspectpObj
     */
    public final function setAspectpDependObj($type, & $aspectpObj = null) {
        if (!is_null($aspectpObj)) {
            switch ($type) {
                case 'aspectp' :
                    if (is_null($this->_aspectMapping)) {
                        $this->_aspectMapping = & $aspectpObj;
                    }
                    break;
                case 'transactional' :
                    if (is_null($this->_aspectDb)) {
                        $this->_aspectDb = & $aspectpObj;
                    }
                    break;
                case 'cacheable' :
                case 'cacheEvict' :
                    if (is_null($this->_aspectCache)) {
                        $this->_aspectCache = & $aspectpObj;
                    }
                    break;
                case 'proxyFactory' :
                    if (is_null($this->_proxyFactory)) {
                        $this->_proxyFactory = & $aspectpObj;
                    }
                    break;
            }
        }
    }

    public final function setPointcutObject($pointcut, & $obj) {
        if (!isset($this->_pointcutObj[$pointcut])) {
            $this->_pointcutObj[$pointcut] = & $obj;
        }
    }

    public final function currentInjectMapping() {
        return $this->_injectMapping;
    }

    /**
     * 判断是否为代理类
     * @return type
     */
    public final function isAopProxy() {
        return $this->_hasJoinPoint;
    }

    public final function currentReflectionClass() {
        return $this->_reflectionClass;
    }

    public final function currentInstances() {
        return $this->_instances;
    }

    public final function & getProxyData() {
        return $this->_data;
    }

    public final function setCacheablePack($value) {
        if (is_null($this->_cacheablePack)) {
            $this->_cacheablePack = $value;
        }
    }

    /**
     * 将 __Handler 中的私有拦截器入栈
     * @param type $value
     */
    public final function setHandlerPrivateInterceptor($value) {
        $this->_injectMapping['interceptor'] = $value;
    }

    /**
     * 根据导入资源的路径为key 导入资源到util中
     * @param type $ips
     * @param type $aryBundles
     */
    public final function setProxyImportResource(& $ips, & $aryBundles) {
        //$this->__get($this->_clazz);
        $_n = null;
        $_p = null;
        foreach ($ips as $_ip) {
            if ($_ip->isDefaultValueAvailable() &&
                ($_n = $_ip->getName()) &&
                ($_p = $_ip->getDefaultValue())
            ) {
//                $this->_setProxyImportResource($_n, $_p, $aryBundles);
                if (strcmp($_n, 'value') == 0) {
                    if (is_array($_p)) {
                        foreach ($_p as $_resource) {
                            require_once APP_PATH . $_resource;
                        }
                    } else {
                        require_once APP_PATH . $_p;
                    }
                } else {
                    if (!isset($aryBundles[$_p]))
                        $aryBundles[$_p] = require ROOT_PATH . $_p;

                    $this->_injectMapping['bundles'][$_n] = $_p; //保持单例
                }
            }
        }
        $_n = null;
        $_p = null;
    }

    /**
     * 获取已经载入的资源，根据变量名匹配，只匹配存在于util池中的资源
     * @param type $bundles
     * @param type $aryData 框架中定义的资源(__xx__)
     * @param type $aryBundles
     */
    public final function getProxyUtil(& $bundles, & $aryData, & $aryBundles) {
        //$this->__get($this->_clazz);
        foreach ($bundles as $_util) {
//            $this->_setProxyUtil($_util->getName(), $aryData, $aryBundles);
            $_n = $_util->getName();
            if (isset($aryData['__ROUTE_CONFIG__']['bundles'][$_n])) {
                $_key = $aryData['__ROUTE_CONFIG__']['bundles'][$_n];

                if (!isset($aryBundles[$_key])) {
                    $aryBundles[$_key] = require ROOT_PATH . $_key;
                }

                $this->_injectMapping['bundles'][$_n] = '@config';
                $_key = null;
            } else if (isset($aryData[$_n])) {
                $this->_injectMapping['bundles'][$_n] = '@data';
            }
        }
//        $_n = null;
    }

    /**
     * 代理类执行时注入的资源
     * @param type $key
     * @param type $value
     * @param type $aryBundles
     */
    private function _setProxyImportResource($key, $value, & $aryBundles) {
        if (is_null($this->_aryTmpProxyUtil)) {
            $this->_aryTmpProxyUtil = array();
        }
        if (strcmp($key, 'value') == 0) {
            if (is_array($value)) {
                foreach ($value as $_resource) {
//                    require_once APP_PATH . $_resource;
                    $this->_aryTmpProxyUtil['bundles'][$_resource] = '@import';
                }
            } else {
//                require_once APP_PATH . $value;
                $this->_aryTmpProxyUtil['bundles'][$value] = '@import';
            }
        } else {
            if (!isset($aryBundles[$value])) {
                $aryBundles[$value] = require ROOT_PATH . $value;
            }

            $this->_aryTmpProxyUtil['bundles'][$key] = $value; //保持单例
        }
    }

    /**
     * 代理类执行时注入的类
     * @param type $key
     * @param type $aryData
     * @param type $aryBundles
     */
    private function _setProxyUtil($key, & $aryData, & $aryBundles) {
        if (is_null($this->_aryTmpProxyUtil)) {
            $this->_aryTmpProxyUtil = array();
        }
        if (isset($aryData['__ROUTE_CONFIG__']['bundles'][$key])) {
            $_key = $aryData['__ROUTE_CONFIG__']['bundles'][$key];

            if (!isset($aryBundles[$_key])) {
                $aryBundles[$_key] = require ROOT_PATH . $_key;
            }

            $this->_aryTmpProxyUtil['bundles'][$key] = '@config';
            $_key = null;
        } else if (isset($aryData[$key])) {
            $this->_aryTmpProxyUtil['bundles'][$key] = '@data';
        }
    }

    /**
     * 将资源映射入栈
     * @param type $className 类名
     * @param type $paramName 变量名
     */
    public final function setProxyInject($className, $paramName) {
        //$this->__get($this->_clazz);
//        logger::debug($this->_clazz . '  ' . $className);
        //调用类的变量名可能不一样，如果class name作为key，则会丢失调用相同类但不同变量名的注入
        if (!isset($this->_injectMapping['injects'][$paramName])) {
            $this->_injectMapping['injects'][$paramName] = $className;
        }
    }

    /**
     * 单例类中单例方法
     * @param $singletonId
     */
    public final function setSingletonInject($singletonId) {
        //$this->__get($this->_clazz);
//        logger::debug($this->_clazz . '  ' . $className);
        //调用类的变量名可能不一样，如果class name作为key，则会丢失调用相同类但不同变量名的注入
        if (!isset($this->_injectMapping['singletons'][$singletonId])) {
            $this->_injectMapping['singletons'][$singletonId] = 0;
        }
    }

    /**
     *
     * @param type $_resource
     */
    public final function importVendorsResource($_resource) {
        require_once APP_PATH . $_resource;
    }

    /**
     * 注入资源
     * @param type $proxyHandlers
     * @param type $aryData
     * @param type $aryBundles
     */
    public final function inject(& $proxyHandlers, & $aryData, & $aryBundles) {
        //die(var_dump($injects));
        //$this->_instances[$clazz]->$name = $injects;
        if (is_null($this->_instances)) {
            $this->_tmpInjectCoordinator = array();
            if (isset($this->_injectMapping['bundles'])) {
                $this->_tmpInjectCoordinator['bundles'] = $this->_injectMapping['bundles'];
            }
            if (isset($this->_injectMapping['injects'])) {
                $this->_tmpInjectCoordinator['injects'] = $this->_injectMapping['injects'];
            }

            if ($this->_isUseReflection) {
                $this->_getInstancesByReflection($proxyHandlers, $aryData, $aryBundles);
            } else {
                $this->_getInstancesByInjectMapping($proxyHandlers, $aryData, $aryBundles);
            }
            //注入其他资源，默认都是public
            if (isset($this->_injectMapping['__InjectData'])) {
                $this->_instances->__InjectData($aryData);
            }
//            logger::debug($this->_instances);
            //注入资源到类实例，注：如果constructor直接取用了注入值，将不会再注入
            //（需要手动注入到类中，否则不会达到预期注入效果）
            if (isset($this->_tmpInjectCoordinator['bundles']) &&
                count($this->_tmpInjectCoordinator['bundles']) > 0
            ) {
                foreach ($this->_tmpInjectCoordinator['bundles'] as $_k => $_type) {
                    //防止注入到类中的data已含有数据导致的重复复制
                    if (isset($this->_injectMapping['__InjectData']) && isset($aryData[$_k])) {
                        //logger::debug($_k);
                        continue;
                    }
                    switch ($_type) {
                        case '@data' :
                            if (isset($this->_injectMapping['__set'])) {
                                $this->_instances->__set($_k, $aryData[$_k]);
                            } else {
                                $this->_instances->$_k = & $aryData[$_k];
                            }
                            break;
                        case '@import' :
                            $this->importVendorsResource($_k);
                            break;
                        case '@config' :
                            $_type = $aryData['__ROUTE_CONFIG__']['bundles'][$_k];
                        default : //ImportResource 将注入附着到类中
                            if (isset($this->_injectMapping['__set'])) {
                                $this->_instances->__set($_k, $aryBundles[$_type]);
                            } else {
                                $this->_instances->$_k = & $aryBundles[$_type];
                            }
                            break;
                    }
                }
            }

            if (isset($this->_tmpInjectCoordinator['injects']) &&
                count($this->_tmpInjectCoordinator['injects']) > 0
            ) {
                foreach ($this->_tmpInjectCoordinator['injects'] as $_param => $_class) {
                    $_classInstances = null;
                    if ($proxyHandlers[$_class]->isAopProxy()) {
                        $_classInstances = $proxyHandlers[$_class];
                    } else {
                        //PHP5中对象直接为引用，不用传址，否则会产生E_STRICT级别消息
                        $_classInstances = $proxyHandlers[$_class]->currentInstances();
                    }
//                    logger::debug($this->_injectMapping);
                    if (!isset($this->_injectMapping['singletons'][$_param])) {
                        $this->_instances->$_param = $_classInstances;
                    } else {
                        if (!isset($proxyHandlers['__SINGLETONS__'][$_param])) {
                            $proxyHandlers['__SINGLETONS__'][$_param] = $_classInstances->$_param(null);
                        }
                        $this->_instances->$_param = $proxyHandlers['__SINGLETONS__'][$_param];
                    }
                }
            }
            $this->_tmpInjectCoordinator = null;
            unset($this->_tmpInjectCoordinator);
        }
    }

    /**
     * 解决传参无法一致性的问题
     * (__call是按照方法的个数打包成数组)
     * proxyFactory中的invoke的已经包裹好的数组在__call中会被打包成一个
     * @param type $name
     * @param type $arguments
     * @return type
     */
    public final function invoke(& $name, & $arguments) {
        //logger::debug('__invoke__ ' . $this->_clazz . ' ' . $name);
        switch (count($arguments)) {
            case 0 :
                return $this->_instances->$name();
                break;
            case 1 :
                /**
                 * 由于__call等魔术方法不支持引用传址，此处作为支持handler中的引用补充
                 * 当handler通过代理执行到此处，方法中运行getData获取内存中地址
                 */
                if ($this->_hasJoinPoint) {
                    $this->_data = & $arguments[0];
                    return $this->_instances->$name($this->_data);
                }
//                logger::debug($name);
//                logger::debug($arguments);
                return $this->_instances->$name($arguments[0]);
                break;
            case 2 :
                return $this->_instances->$name($arguments[0], $arguments[1]);
                break;
            case 3 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2]);
                break;
            case 4 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2], $arguments[3]);
                break;
            case 5 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2], $arguments[3], $arguments[4]);
                break;
            case 6 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2], $arguments[3], $arguments[4], $arguments[5]);
                break;
            case 7 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2], $arguments[3], $arguments[4], $arguments[5],
                    $arguments[6]);
                break;
            case 8 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2], $arguments[3], $arguments[4], $arguments[5],
                    $arguments[6], $arguments[7]);
                break;
            case 9 :
                return $this->_instances->$name($arguments[0], $arguments[1],
                    $arguments[2], $arguments[3], $arguments[4], $arguments[5],
                    $arguments[6], $arguments[7], $arguments[8]);
                break;
            default :
                if (!is_null($this->_reflectionClass))
                    return $this->_reflectionClass->getMethod($name)->invokeArgs($this->_instances, $arguments);
                return call_user_func_array(array($this->_instances, $name), $arguments);
                break;
        }
//		if (is_null($this->_reflectionClass)) {
//			if (count($arguments) > 0) {
//				return $this->_reflectionClass->getMethod($name)->invokeArgs($this->_instances, $arguments);
//			}
//			return $this->_reflectionClass->getMethod($name)->invoke($this->_instances);
//		} else {}
    }

    public final function & proceed() {
        if ($this->_hasJoinPoint) {//如果存在切点
            $this->_proceedResult = null;
            $_isTransactional = false;
            if ($this->checkJoinPointProperty('transactional', '__Transactional') ||
                $this->checkJoinPointProperty('transactional', $this->_currentMethod)
            ) {
                $_isTransactional = true;
            }
            $_cacheableValue = null;
            $_isCacheable = false; //是否写入缓存

            if ($this->checkJoinPointProperty('cacheable', $this->_currentMethod)) {
                $_cacheableValue = $this->_injectMapping['aspectMapping']['cacheable'][$this->_currentMethod];
            } else if ($this->checkJoinPointProperty('cacheable', '__Cacheable')) {
                $_cacheableValue = $this->_injectMapping['aspectMapping']['cacheable']['__Cacheable'];
                if (is_null($_cacheableValue[0])) {
                    $_cacheableValue[0] = $this->_currentMethod;
                } else {
                    $_cacheableValue[0] .= $this->_currentMethod;
                }
            }

            if ($this->checkJoinPointProperty('value', $this->_currentMethod)) {
                $_values = json_decode(
                    $this->_injectMapping['aspectMapping']['value'][$this->_currentMethod], true);
                if (is_array($_values)) {
                    foreach ($_values as $_v) {
                        $this->_setProxyUtil($_v, $this->_proxyFactory->getData(), $this->_proxyFactory->getBundles());
                    }
                } else {
                    $this->_setProxyUtil($_values, $this->_proxyFactory->getData(), $this->_proxyFactory->getBundles());
                }
            }
            if ($this->checkJoinPointProperty('importResource', $this->_currentMethod)) {
                $_values = json_decode(
                    $this->_injectMapping['aspectMapping']['importResource'][$this->_currentMethod], true);
                if (is_array($_values)) {
                    foreach ($_values as $_k => $_v) {
                        if (is_numeric($_k)) {
                            $this->_setProxyImportResource('value', $_v, $this->_proxyFactory->getBundles());
                        } else {
                            $this->_setProxyImportResource($_k, $_v, $this->_proxyFactory->getBundles());
                        }
                    }
                } else {
                    $this->_setProxyImportResource('value', $_values, $this->_proxyFactory->getBundles());
                }
            }
            if (isset($this->_aryTmpProxyUtil['bundles']) &&
                count($this->_aryTmpProxyUtil['bundles']) > 0
            ) {
                foreach ($this->_aryTmpProxyUtil['bundles'] as $_k => $_type) {
                    switch ($_type) {
                        case '@data' :
                            if (isset($this->_injectMapping['__set'])) {
                                $this->_instances->__set($_k, $this->_proxyFactory->getData($_k));
                            } else {
                                $this->_instances->$_k = & $this->_proxyFactory->getData($_k);
                            }
                            break;
                        case '@import' :
                            $this->importVendorsResource($_k);
                            break;
                        case '@config' :
                            $_type = $this->_proxyFactory->getDataRouteConfig('bundles', $_k);
                        default : //ImportResource 将注入附着到类中
                            if (isset($this->_injectMapping['__set'])) {
                                $this->_instances->__set($_k, $this->_proxyFactory->getBundles($_type));
                            } else {
                                $this->_instances->$_k = & $this->_proxyFactory->getBundles($_type);
                            }
                            break;
                    }
                }
            }

            /**
             * 含有方法级注入时的检查及赋值
             */
            if ($this->checkJoinPointProperty('inject', $this->_currentMethod)) {
                $_injectValue = json_decode($this->_injectMapping['aspectMapping']['inject'][$this->_currentMethod], true);
                $_singletons = json_decode($this->_injectMapping['aspectMapping']['singleton'][$this->_currentMethod], true);
                $_aryProperty = array();

                if (count($_injectValue) > 0) { //修正后的注入
                    foreach ($_injectValue as $_paramName => $_className) {
                        $this->_proxyFactory->inject($_className);
                        $this->setProxyInject($_className, $_paramName);
                        if (isset($_singletons[$_paramName])) {
                            $this->setSingletonInject($_paramName);
                        }
                        $_aryProperty[$_paramName] = $_className;
                    }
                }

//                if (is_array($_injectValue)) { //__Inject = array('db', '\Service\Helper' => 'helper')
//                    foreach ($_injectValue as $_className => $_paramName) {
//                        if (is_numeric($_className)) {
//                            $_injects = $this->_proxyFactory->getDataRouteConfig();
//                            if (isset($_injects[$_paramName])) {
//                                $this->_proxyFactory->inject($_injects[$_paramName]);
//                                $this->setProxyInject($_injects[$_paramName], $_paramName);
//                                $_aryProperty[$_paramName] = $_injects[$_paramName];
//                            }
//                        } else {
//                            $this->_proxyFactory->inject($_className);
//                            $this->setProxyInject($_className, $_paramName);
//                            $_aryProperty[$_paramName] = $_className;
//                        }
//                    }
//                } else {// __Inject = 'db'
//                    $_injects = $this->_proxyFactory->getDataRouteConfig();
//                    if (isset($_injects[$_injectValue])) {
//                        $this->_proxyFactory->inject($_injects[$_injectValue]);
//                        $this->setProxyInject($_injects[$_injectValue], $_injectValue);
//                        $_aryProperty[$_injectValue] = $_injects[$_injectValue];
//                    }
//                }

                if (count($_aryProperty) > 0) {
//                    logger::d($this->_injectMapping['singletons']);
                    foreach ($_aryProperty as $_param => $_clazz) {
                        $_classInstances = $this->_proxyFactory->getProxyHandlers($_clazz);
                        if (!$_classInstances->isAopProxy()) {
                            //PHP5中对象直接为引用，不用传址，否则会产生E_STRICT级别消息
                            $_classInstances = $_classInstances->currentInstances();
                        }

                        if (!isset($this->_injectMapping['singletons'][$_param])) {
                            $this->_instances->$_param = $_classInstances;
                        } else {
                            if (!$this->_proxyFactory->hasSingleton($_param)) {
//                                $proxyHandlers['__SINGLETONS__'][$_param] = $_classInstances->$_param(null);
                                $this->_proxyFactory->setSingleton($_param, $_classInstances->$_param(null));
                            }
                            $this->_instances->$_param = $this->_proxyFactory->getSingleton($_param);
                        }

                    }
                }
                unset($_aryProperty);
                $_aryProperty = null;
            }

            if (!is_null($_cacheableValue)) {

                $_cacheableValue[0] = $this->_cacheablePack . $_cacheableValue[0];

                if (!($this->_proceedResult = $this->_aspectCache->mode($_cacheableValue[2])
                    ->expires($_cacheableValue[1])
                    ->get($_cacheableValue[0]))
                ) {//如果缓存未命中
                    $_isCacheable = true;
                }
            }

            $_cacheEvictValue = null;
            if ($this->checkJoinPointProperty('cacheEvict', $this->_currentMethod)) {
                $_cacheEvictValue = $this->_injectMapping['aspectMapping']['cacheEvict'][$this->_currentMethod];
            } else if ($this->checkJoinPointProperty('cacheEvict', '__CacheEvict')) {
                $_cacheEvictValue = $this->_injectMapping['aspectMapping']['cacheEvict']['__CacheEvict'];
            }

            if (isset($this->_injectMapping['aspectMapping']['aspectp']) &&
                count($this->_injectMapping['aspectMapping']['aspectp']) > 0
            ) {
                $_throwing = false;
                foreach ($this->_injectMapping['aspectMapping']['aspectp'] as $_pointcut => $_disabled) {
                    if (isset($this->_aspectMapping[$_pointcut]['before'])) {
                        $_disabled = $this->_aspectMapping[$_pointcut]['before']['method'];
                        if (isset($this->_aspectMapping[$_pointcut]['before']['joinPoint'])) {
                            //注入反射的方法
                            //返回本对象的反射类给切面连接点，所以在走缓存的时候未获取反射则需要获取一次
                            if (is_null($this->_reflectionClass)) {
                                $this->_reflectionClass = new ReflectionClass($this->_clazz);
                            }
                            $this->_pointcutObj[$_pointcut]->$_disabled(
                                $this->_reflectionClass->getMethod($this->_currentMethod)
                            );
                        } else {
                            $this->_pointcutObj[$_pointcut]->$_disabled();
                        }
                    }
                    //抛出异常如果有环绕通知则不进行，应该交由用户自行控制
                    if (isset($this->_aspectMapping[$_pointcut]['afterThrowing']) &&
                        !isset($this->_aspectMapping[$_pointcut]['around'])
                    ) {
                        $_throwing = true;
                    }
                }
                if (is_null($this->_proceedResult) && ($_throwing || $_isTransactional)) {
                    try {
                        if ($_isTransactional) {
                            $this->_aspectDb->beginTransaction();
                        }

                        $this->_proceedResult = $this->invoke($this->_currentMethod, $this->_arguments);

                        if ($_isTransactional) {
                            $this->_aspectDb->commit();
                        }
                    } catch (Exception $e) {
                        if ($_isTransactional) {
                            $this->_aspectDb->rollBack();
                        }

                        if ($_throwing && isset($this->_aspectMapping[$_pointcut]['afterThrowing']['throwing'])) {
                            $_disabled = $this->_aspectMapping[$_pointcut]['afterThrowing']['method'];
                            $this->_pointcutObj[$_pointcut]->$_disabled($e);
                        }
                    }
                } else {
                    //用户自定义行为由于不可控，当使用环绕通知的时候afterThrowing以及Transactional不会执行
                    //需要用户自行控制
                    if (is_null($this->_proceedResult)) {
                        $this->_proceedResult = $this->invoke($this->_currentMethod, $this->_arguments);
                    }
                }
                //逆向执行后面切片
                $_reverseAspectp = array_reverse($this->_injectMapping['aspectMapping']['aspectp'], true);
                foreach ($_reverseAspectp as $_pointcut => $_disabled) {
                    if (isset($this->_aspectMapping[$_pointcut]['afterReturning'])) {
                        $_disabled = $this->_aspectMapping[$_pointcut]['afterReturning']['method'];
                        if (isset($this->_aspectMapping[$_pointcut]['afterReturning']['returning'])) {
                            $this->_pointcutObj[$_pointcut]->$_disabled($this->_proceedResult);
                        } else {
                            $this->_pointcutObj[$_pointcut]->$_disabled();
                        }
                    }
                    if (isset($this->_aspectMapping[$_pointcut]['after'])) {
                        $_disabled = $this->_aspectMapping[$_pointcut]['after'];
                        $this->_pointcutObj[$_pointcut]->$_disabled();
                    }
                }
                $_reverseAspectp = null;
                unset($_reverseAspectp);
            } else {
                if (is_null($this->_proceedResult) && $_isTransactional) {
                    try {
                        $this->_aspectDb->beginTransaction();
                        $this->_proceedResult = $this->invoke($this->_currentMethod, $this->_arguments);
                        $this->_aspectDb->commit();
                    } catch (Exception $e) {
                        $this->_aspectDb->rollBack();
                    }
                } else {
                    if (is_null($this->_proceedResult))
                        $this->_proceedResult = $this->invoke($this->_currentMethod, $this->_arguments);
                }
            }
            if (!is_null($_cacheEvictValue)) {
                if (is_array($_cacheEvictValue)) {
                    foreach ($_cacheEvictValue as $_id) {
                        $this->_aspectCacheDelete($_id);
                    }
                } else {
                    $this->_aspectCacheDelete($_cacheEvictValue);
                }
            }
            if ($_isCacheable) { //在缓存不存在或者未命中的情况下都写入一次缓存
                $this->_aspectCache->mode($_cacheableValue[2])->expires($_cacheableValue[1])
                    ->set($_cacheableValue[0], $this->_proceedResult);
            }
            //如果返回值为对象则判定为链式操作，返回代理类 *****
            if (is_object($this->_proceedResult)) {
                //logger::debug(get_class($this->_proceedResult));
                return $this;
            }
            return $this->_proceedResult;
        }
        return $this->invoke($this->_currentMethod, $this->_arguments);
    }

    /**
     * 支持指定介质的缓存删除
     * @param type $cacheId
     */
    private function _aspectCacheDelete($cacheId) {
        $_tmp = array();
        if (strpos($cacheId, ':') !== false) //如果指定了缓存介质
            $_tmp = explode(':', $cacheId);
        else {
            $_tmp[0] = $cacheId;
            $_tmp[1] = null;
        }
        $_tmp[0] = $this->_cacheablePack . $_tmp[0];
        $this->_aspectCache->mode($_tmp[1])->delete($_tmp[0]);
    }

    /**
     * 代理，这里可以执行AOP切片
     * @param type $method
     * @param type $arguments
     * @return type
     */
    public final function & __call($method, $arguments) {
        //logger::debug('__call ' . $this->_clazz . ' ' . $name);
        $_res = null;
        if ($this->_hasJoinPoint) {//如果存在切点
            $this->_currentMethod = & $method;
            $this->_arguments = & $arguments;
            //如果aop映射存在说明需要执行aop
            if ($this->checkJoinPointProperty('aspectp')) {
                foreach ($this->_injectMapping['aspectMapping']['aspectp'] as $_pointcut => $_disabled) {
                    //滤除本方法不需要执行的aop
                    $_hit = false;
                    if (is_array($this->_aspectMapping[$_pointcut]['methods'])) {
                        foreach ($this->_aspectMapping[$_pointcut]['methods'] as $_m) {
                            if ((strpos($this->_aspectMapping[$_pointcut]['methods'], '*') !== false &&
                                    preg_match('/' . $_m . '/Ui',
                                        $method)) || strcmp($_m, $method) == 0
                            ) {
                                $_hit = true;
                                break;
                            }
                        }
                    } else if ((strpos($this->_aspectMapping[$_pointcut]['methods'], '*') !== false &&
                            preg_match('/' . $this->_aspectMapping[$_pointcut]['methods'] . '/Ui', $method)) ||
                        strcmp($this->_aspectMapping[$_pointcut]['methods'], $method) == 0
                    ) {
                        $_hit = true;
                    }
                    if (false === $_hit) {
                        unset($this->_injectMapping['aspectMapping']['aspectp'][$_pointcut]);
                    }
                }
                if (isset($this->_injectMapping['aspectMapping']['aspectp']) &&
                    count($this->_injectMapping['aspectMapping']['aspectp']) > 0
                ) {
                    foreach ($this->_injectMapping['aspectMapping']['aspectp'] as $_pointcut => $_disabled) {
                        if (isset($this->_aspectMapping[$_pointcut]['around']['proceedingJoinPoint'])) {
                            //$_disabled 省略重新使用一个变量
                            $_disabled = $this->_aspectMapping[$_pointcut]['around']['method'];
                            //执行around方法并注入此代理类
                            $this->_pointcutObj[$_pointcut]->$_disabled($this);
                            if (is_null($this->_proceedResult)) {
                                logger::error('FATAL CODE: 0x00001001');
                            }
                            return $this->_proceedResult;
                        } else {
                            return $this->proceed();
                        }
                    }
                }
            }
//            $_res = $this->proceed();
            return $this->proceed();
        }
        return $this->invoke($method, $arguments);
//        return $_res;
    }

    public final function __set($name, $value) {
        //logger::debug('__set ' . $this->_clazz . ' ' . $name);
        return $this->_instances->__set($name, $value);
    }

    public final function & __get($name) {
        //logger::debug('__get ' . $this->_clazz . ' ' . $name);
        return $this->_instances->__get($name);
    }

    /**
     * 通过反射自建注入，并生成__construct等缓存映射
     * @param type $_proxyHandlers
     * @param type $aryData
     * @param type $aryBundles
     */
    private function _getInstancesByReflection(& $_proxyHandlers, & $aryData, & $aryBundles) {
        //logger::debug($this->_clazz);
        $_args = null;
        $_constructInjectData = false;
        //构造器注入
        if (($_con = $this->_reflectionClass->getConstructor()) &&
            count($_comp = $_con->getParameters()) > 0
        ) {
            $this->_injectMapping['__construct'] = array();
            $_injectId = lcfirst($this->_reflectionClass->getShortName());//5.3+
            if (isset($aryData['__ROUTE_CONFIG__']['injects'][$_injectId]['property'])) {
                $this->_injectMapping['property'] = $_injectId;
            } else if (isset($aryData['__ROUTE_CONFIG__']['injects'][$this->_clazz]['property'])) {
                $this->_injectMapping['property'] = $this->_clazz;
            }
            foreach ($_comp as $_p) {
                $_n = $_p->getName();
                //如果变量名一致则直接从构造器中注入
                //构造器中设置了 Array & $__InjectData 则会将框架输入直接注入
                if (strcmp('__InjectData', $_n) == 0 &&
                    $_p->isPassedByReference() &&
                    $_p->isArray()
                ) {
                    $this->_injectMapping['__construct'][] = array('@injectData' => 0);
                    $_constructInjectData = true;
                    $_args[] = & $aryData;
                } else if (isset($this->_injectMapping['bundles'][$_n])) {
                    $this->_injectMapping['__construct'][] = array('@bundles' => $_n);

                    switch ($this->_injectMapping['bundles'][$_n]) {
                        case '@data' :
                            $_args[] = & $aryData[$_n];
                            break;
                        case '@config' :
                            $_args[] = & $aryBundles[$aryData['__ROUTE_CONFIG__']['bundles'][$_n]];
                            break;
                        default : //ImportResource
                            $_args[] = & $aryBundles[$_n];
                            break;
                    }

                    //logger::debug($_n);
                    unset($this->_tmpInjectCoordinator['bundles'][$_n]);
                } else if (isset($this->_injectMapping['injects'][$_n])) {//匹配变量名
                    $this->_injectMapping['__construct'][] = array('@injects' => $_n);
                    //使用代理免疫直接返回类的实例而不是代理类
                    //根据类名获取
                    if ($_proxyHandlers[$this->_injectMapping['injects'][$_n]]->isAopProxy()) {
                        $_args[] = $_proxyHandlers[$this->_injectMapping['injects'][$_n]];
                    } else {
                        //PHP5中对象直接为引用，不用传址，否则会产生E_STRICT级别消息
                        $_args[] = $_proxyHandlers[$this->_injectMapping['injects'][$_n]]->currentInstances();
                    }

                    unset($this->_tmpInjectCoordinator['injects'][$_n]);
                } else if (isset($this->_injectMapping['property']) &&
                    isset($aryData['__ROUTE_CONFIG__']['injects']
                        [$this->_injectMapping['property']]['property'][$_n])
                ) {
                    $this->_injectMapping['__construct'][] = array('@property' => $_n);
                    $_args[] = $aryData['__ROUTE_CONFIG__']['injects']
                    [$this->_injectMapping['property']]['property'][$_n];
                } else if ($_p->isDefaultValueAvailable()) {
                    $this->_injectMapping['__construct'][] = array('@normal' => $_p->getDefaultValue());
                    $_args[] = $_p->getDefaultValue();
                } else {
                    $this->_injectMapping['__construct'][] = array('@normal' => null);
                    $_args[] = null;
                }
            }
        }
        //入参实例化
        if (count($_args) > 0) {
            /**
             * Note: ReflectionClass::newInstanceArgs() is available for PHP 5.1.3+
             * else
             * $this->_instances[$clazz] =
             *        = call_user_func_array(array($this->_reflectionClass[$clazz],'newInstance'),$_args);
             */
            $this->_instances = $this->_reflectionClass->newInstanceArgs($_args);
        } else {
            $this->_instances = $this->_reflectionClass->newInstance();
        }

        //__construct 中 __InjectData优先级最高，其次注入方法__InjectData，最后由反射注入
        if (!$_constructInjectData && $this->_reflectionClass->hasMethod('__InjectData')) {
            $this->_injectMapping['__InjectData'] = 0;
        }
        if ($this->_reflectionClass->hasMethod('__set')) {
            $this->_injectMapping['__set'] = 0;
        }

        if ($this->_reflectionClass->hasMethod('__Controller') ||
            $this->_reflectionClass->hasMethod('__RestController')) {
            $this->_injectMapping['__route'] = 0;
            if (isset($aryData['__PATH_VARIABLE__']) &&
                strcmp($this->_clazz, $aryData['__CURRENT_TRIGGER_CLASS__']) == 0) {
                foreach ($aryData['__PATH_VARIABLE__'] as $_k => $_v) {
                    $this->_instances->$_k = $_v;
                }
                unset($aryData['__PATH_VARIABLE__']);
            }
        }

    }

    /**
     * 通过缓存自建注入
     * @param type $_proxyHandlers
     * @param type $aryData
     * @param type $aryBundles
     */
    private function _getInstancesByInjectMapping(& $_proxyHandlers, & $aryData, & $aryBundles) {
        $_args = null;
        //构造器注入
        if (isset($this->_injectMapping['__construct'])) {
            $_tmpCurrent = null;
            foreach ($this->_injectMapping['__construct'] as $_v) {
                switch (key($_v)) {
                    case '@injectData' :
                        $_args[] = & $aryData;
                        break;
                    case '@bundles' :
                        $_tmpCurrent = current($_v);
                        switch ($this->_injectMapping['bundles'][$_tmpCurrent]) {
                            case '@data' :
                                $_args[] = & $aryData[$_tmpCurrent];
                                break;
                            case '@config' :
                                $_args[] = & $aryBundles[$aryData['__ROUTE_CONFIG__']['bundles'][$_tmpCurrent]];
                                break;
                            default : //ImportResource
                                $_args[] = & $aryBundles[$_tmpCurrent];
                                break;
                        }
                        unset($this->_tmpInjectCoordinator['bundles'][$_tmpCurrent]);
                        break;
                    case '@injects' :
                        $_tmpCurrent = current($_v);
                        //logger::debug($this->_injectMapping['injects']);
                        //如果含有aop切入点，返回代理类
                        if ($_proxyHandlers[$this->_injectMapping['injects'][$_tmpCurrent]]->isAopProxy()) {
                            $_args[] = $_proxyHandlers[$this->_injectMapping['injects'][$_tmpCurrent]];
                        } else {
                            $_args[] = $_proxyHandlers[$this->_injectMapping['injects']
                            [$_tmpCurrent]]->currentInstances();
                        }
                        unset($this->_tmpInjectCoordinator['injects'][$_tmpCurrent]);
                        break;
                    case '@normal' :
                        $_args[] = current($_v);
                        break;
                    case '@property' :
                        $_args[] = $aryData['__ROUTE_CONFIG__']['injects']
                        [$this->_injectMapping['property']]['property'][current($_v)];
                        break;
                    default :
                        $_args[] = null;
                        break;
                }
            }
            $_tmpCurrent = null;
        }
        //入参实例化
        switch (count($_args)) {
            case 0 :
                $this->_instances = new $this->_clazz();
                break;
            case 1 :
                $this->_instances = new $this->_clazz($_args[0]);
                break;
            case 2 :
                $this->_instances = new $this->_clazz($_args[0], $_args[1]);
                break;
            case 3 :
                $this->_instances = new $this->_clazz($_args[0],
                    $_args[1], $_args[2]);
                break;
            case 4 :
                $this->_instances = new $this->_clazz($_args[0],
                    $_args[1], $_args[2], $_args[3]);
                break;
            case 5 :
                $this->_instances = new $this->_clazz($_args[0],
                    $_args[1], $_args[2], $_args[3], $_args[4]);
                break;
            case 6 :
                $this->_instances = new $this->_clazz($_args[0], $_args[1],
                    $_args[2], $_args[3], $_args[4], $_args[5]);
                break;
            case 7 :
                $this->_instances = new $this->_clazz($_args[0], $_args[1],
                    $_args[2], $_args[3], $_args[4], $_args[5], $_args[6]);
                break;
            case 8 :
                $this->_instances = new $this->_clazz($_args[0], $_args[1],
                    $_args[2], $_args[3], $_args[4], $_args[5], $_args[6], $_args[7]);
                break;
            case 9 :
                $this->_instances = new $this->_clazz($_args[0], $_args[1],
                    $_args[2], $_args[3], $_args[4], $_args[5], $_args[6], $_args[7], $_args[8]);
                break;
            default :
                //实例化传参没有更好的解决方法，类初始化参数太多还是借助反射(性能略微损耗，7参数以下不会触发)
                if (is_null($this->_reflectionClass))
                    $this->_reflectionClass = new ReflectionClass($this->_clazz);
                $this->_instances = $this->_reflectionClass->newInstanceArgs($_args);
                //call_user_func_array(array($this->_instances, '__construct'), $_args);
                break;
        }

        if (isset($this->_injectMapping['__route']) &&
            isset($aryData['__PATH_VARIABLE__']) &&
            strcmp($this->_clazz, $aryData['__CURRENT_TRIGGER_CLASS__']) == 0) {
            foreach ($aryData['__PATH_VARIABLE__'] as $_k => $_v) {
                $this->_instances->$_k = $_v;
            }
            unset($aryData['__PATH_VARIABLE__']);
        }
    }

}
