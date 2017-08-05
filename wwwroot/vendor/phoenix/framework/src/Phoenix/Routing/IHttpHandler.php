<?php

namespace Phoenix\Routing;

if (!defined('IN_PX'))
    exit;

/**
 * 速度极快的快速访问接口，不需要加载任何页面
 * handler返回
 */
interface IHttpHandler {

    /**
     * 处理进程
     * $context 为注入的上下文环境，即_data[]数组环境
     */
    public function processRequest(Array & $context);
}
