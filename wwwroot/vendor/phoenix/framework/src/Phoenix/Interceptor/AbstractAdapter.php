<?php

namespace Phoenix\Interceptor;

if (!defined('IN_PX'))
    exit;


/**
 * 拦截器适配器抽象类
 * 拦截器可以继承此类或者单独实现 \Phoenix\IInterceptor 接口
 * 适配器可以扩展额外的工作
 *
 */
abstract class AbstractAdapter implements IInterceptor {

    protected function __Value($cfg) {}

    //public function __construct() {}
    public function preHandle(Array & $context) {}

    public function postHandle(Array & $context) {}

    public function afterCompletion(Array & $context) {}

    /**
     * 销毁变量
     */
    public function __destruct() {}

}
