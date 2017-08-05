<?php

namespace Phoenix\Routing;

if (!defined('IN_PX'))
    exit;

use Phoenix\Log\Log4p as logger;

/**
 * 获取框架环境的上下文，用于单元测试(如 phpunit)
 */
class Context extends ProxyFactory {

    const VERSION = '0.0.2';

    public function handler(Array & $context) {
        if (!empty($context)) {
            $this->context = & $context;
        }

        //赋值config信息
        parent::_getRouteConfig();

        if (is_null($this->_injectMapping)) {
            $this->_injectMapping = $this->_cacheInjectMappingHolder;
        }
        if (is_null($this->_frameworkCache)) {
            $this->_frameworkCache = $this->inject($this->context['__ROUTE_CONFIG__']['frameworkCache']);
        }

        $this->_createCache(false);
    }

    public static function create(Array & $context = array()) {
        if (is_null(static::$_instances)) {
            static::$_instances = new self();
        }
        return static::$_instances->handler($context);
    }

}
