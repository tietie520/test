<?php

namespace Phoenix\Interceptor;

if (!defined('IN_PX'))
    exit;

/**
 * 拦截器的接口，拦截器类必须实现此接口
 * 具体的拦截器类可继承抽象类\Phoenix\AbstractAdapter或者实现此接口
 */
interface IInterceptor {

    /**
     * Controller执行前执行
     * @param array $context 参数由框架传址，直接修改添加同步框架数据。上下文
     * @return bool 返回true or false
     */
    public function preHandle(Array & $context);

    /**
     * Controller执行后执行
     * @param array $context
     * 可直接修改data视图
     */
    public function postHandle(Array & $context);

    /**
     * 页面渲染完毕后执行
     * @param array $context
     */
    public function afterCompletion(Array & $context);
}
