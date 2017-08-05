<?php
namespace Phoenix\Foundation;

//if (!defined('IN_PX'))
//    exit;

use Phoenix\Routing\Controller;
use Phoenix\Routing\HttpHandler;
use Phoenix\Routing\Context;
use Phoenix\RPC\Server;
use Phoenix\Log\Log4p as logger;

class Application {

    const VERSION = '1.0.12';

    /**
     * application bootstrap
     * @param null $pathInfo
     */
    public static function bootstrap($pathInfo = null) {
        if (strcasecmp('HEAD', $_SERVER['REQUEST_METHOD']) == 0) {
            header('HTTP/1.1 200 OK');
            exit;
        }

        if (is_null($pathInfo)) {
            if (substr(PHP_SAPI, 0, 3) == 'cli') {
                $_GET = array_slice($_SERVER['argv'], 1);
            }
            self::getRequestURI();
            $pathInfo = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $pathInfo = urldecode($pathInfo);
        }

        self::dispatcher($pathInfo);
    }

    /**
     * 路由分发
     * @param $pathInfo
     * @param bool $isHookApi
     * @return mixed
     */
    public static function dispatcher($pathInfo, $isHookApi = false) {
        set_exception_handler(array('Phoenix\Exception\FormatException', 'staticException'));

        $_context = array();

        if (PX_DEBUG && !$isHookApi) {
            $_context['__S_RUNTIME__'] = microtime(true);
            $_context['__S_MEMORY_USE__'] = memory_get_usage();
        }
        $pathInfo = trim($pathInfo, '/');

        $_context['__PATHS__'] = array();
        $_isHandler = false;
        $_isRPC = false;
        if ($pathInfo != '') {
            //清除路径上的html标签
            $pathInfo = strip_tags($pathInfo);
            $pathInfo = htmlspecialchars($pathInfo);
            $_context['__EXTENSIONS__'] = strtolower(pathinfo($pathInfo, PATHINFO_EXTENSION)); //扩展名
            $_extensionsExists = !empty($_context['__EXTENSIONS__']);
            if (!$_extensionsExists) {
                $_context['__EXTENSIONS__'] = 'php';
            }

            $_context['__ROUTE_CONFIG__'] = require_once APP_PATH . 'route.config.php';
            $_context['__SUFFIX__'] = & $_context['__ROUTE_CONFIG__']['suffix'];
            $_context['__ROOT__'] = & $_context['__ROUTE_CONFIG__']['root'];

            //清除路径上的前缀
            if ($_context['__ROOT__'] != '/') {
                $_root = trim($_context['__ROOT__'], '/');
                if (strpos($pathInfo, $_root) === 0) {
                    $pathInfo = substr_replace($pathInfo, '', 0, strlen($_root));
                    $pathInfo = trim($pathInfo, '/');
                }
            }

            $_isHandler = stripos($pathInfo, 'handler/') === 0;

            //RPC连接
            $_isRPC = strcasecmp($pathInfo, $_context['__ROUTE_CONFIG__']['rpcTriggerAlias']) === 0;

            //开发模式下将不支持的文档直接返回404
            //support by webpack hot-dev-server
            if (PX_DEBUG && count($_context['__ROUTE_CONFIG__']['ejector']) > 0) {
                foreach ($_context['__ROUTE_CONFIG__']['ejector'] as $_ejector) {
                    if (strpos($pathInfo, $_ejector) !== false) {
                        header('HTTP/1.1 404 Not Found');
                        header('Status: 404 Not Found');
                        exit;
                    }
                }
            }

            if (false === $_isHandler) {
                if ($_extensionsExists) {//有后缀
                    //框架不支持的文档后缀直接返回404，不启动框架解析
                    if (!in_array($_context['__EXTENSIONS__'], $_context['__SUFFIX__'])) {
                        header('HTTP/1.1 404 Not Found');
                        header('Status: 404 Not Found');
                        if (isset($_context['__ROUTE_CONFIG__'][404]) &&
                            !empty($_context['__ROUTE_CONFIG__'][404])) {
                            include ROOT_PATH . ltrim($_context['__ROUTE_CONFIG__'][404], '/');
                        }
                        exit;
                    }
                    $pathInfo = substr($pathInfo, 0, strrpos($pathInfo, '.'));
                }

                //首页忽略文档类型
                $_context['__MIME__'] = Controller::mime($_context['__EXTENSIONS__']);
            } else {//handle 支持类似.json .xml后缀请求
                if ($_extensionsExists && in_array($_context['__EXTENSIONS__'], $_context['__SUFFIX__'])) {
                    $pathInfo = substr($pathInfo, 0, strrpos($pathInfo, '.'));
                }
            }

            if (false === $_isRPC) {
                $_context['__PATHS__'] = explode('/', $pathInfo);
                $_context['__PACKAGE__'] = array_shift($_context['__PATHS__']);
            }
        }

        if ($_isRPC) {
            set_exception_handler(array('Phoenix\Exception\RPCException', 'staticException'));
            $_context['__CONTROLLER_INDEX__'] = 'rpc';
            return Server::process($_context, $isHookApi);
        } else if ($_isHandler) {
            $_context['__CONTROLLER_INDEX__'] = 'handler';
            return HttpHandler::process($_context, $isHookApi);
        } else {
            return Controller::start($_context, $isHookApi);
        }
    }

    /**
     * 获取px环境上下文，主要用于单元测试
     * @return mixed
     */
    public static function context() {
        Context::create();
        return Context::$_instances;
    }

    /**
     * 内部hook，默认的格式为json，此处直接输出json数组给内部调用者
     * @param type $pathInfo
     * @return type
     */
    public static function hookApi($pathInfo) {
        return json_decode(self::dispatcher($pathInfo, true), true);
    }

    /**
     * getRequestURI
     */
    public static function getRequestURI() {
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } else if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
        } else if (empty($_SERVER['REQUEST_URI'])) {
            if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['ORIG_PATH_INFO'])) {
                $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];
            }
            if (isset($_SERVER['PATH_INFO'])) {
                if ($_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME']) {
                    $_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
                } else {
                    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
                }
            }
            if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
                $_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
            }
        }
        if (strpos($_SERVER['REQUEST_URI'], 'app.php') !== false) {
            $_uri = explode('app.php', $_SERVER['REQUEST_URI'], 2);
            $_SERVER['REQUEST_URI'] = $_uri[1];
            unset($_uri);
            $_uri = null;
        }
    }

}
