<?php

namespace Phoenix\SysPlugin;

if (!defined('IN_PX'))
    exit;

/**
 * 系统级插件的抽象类
 * 系统插件继承此类
 */
abstract class AbstractSysPlugin {

    protected $_data = array();

    public function __InjectData(Array & $data) {
        $this->_data = & $data;
    }

    /**
     * 处理进程 afterRouteConfig
     * parent::_getRouteConfig() 之后执行
     * Controller 以及 HttpHandler 都会执行
     */
    abstract function init();

    /**
     * $this->_ParseRoutes()之后执行
     * Controller 以及 HttpHandler 都会执行
     */
    abstract function afterParseRoutes();

    /**
     * 分析controller的分支，非controller
     */
    abstract function branchDissectionController($reflectionClass, $clazz);

    /**
     * 在获取了_controllers之后执行
     */
    abstract function afterReadControllers();

    /**
     * 在注入了url参数之后执行
     */
    abstract function afterPushUrlParameter();

    /**
     * 在加载视图或输出结果之前执行
     */
    abstract function beforeViewLoader();

    public function __get($name) {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }
        return null;
    }

    public function __set($name, $value) {
        return $this->_data[$name] = $value;
    }

}
