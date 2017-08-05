<?php

namespace Phoenix\RPC;

if (!defined('IN_PX'))
    exit;

use Phoenix\Exception\RPCException;
use Phoenix\Routing\ProxyFactory;
use Phoenix\Support\Array2XML;
use Phoenix\Support\XML2Array;
use Phoenix\Support\Helpers;
use Phoenix\Support\MsgHelper;
use Phoenix\Log\Log4p as logger;

/**
 * Json RPC 不走路由查找，比 Rest Api 性能高。除了调用方式不同，与`HttpHandler`极为相似
 * 如果需要获取更高性能需要用 socket
 */
class Server extends ProxyFactory {

    const VERSION = '0.0.1';

    private $_aryHookApiUri = null;
    private $_currentBuffer = null;
    private $_request = null;
    private $_requestType = 'json';

//    private function __construct() {
//        
//    }

    public function handler(Array & $context, $isHookApi = false) {
        $this->_currentBuffer = null;
        if ($isHookApi) {
//            $this->_data['__HOOK_API_FLAG__'] = true;
            if (is_null($this->_aryHookApiUri)) {
                $this->_aryHookApiUri = array();
            }
            array_push($this->_aryHookApiUri, $this->context);
        }
        if (!empty($context)) {//除了传参取址，赋值的同时也必须引用
            $this->context = & $context;
        }

        $this->_parseParam($isHookApi);
        //赋值config信息
        parent::_getRouteConfig();

        $this->_importSysPlugin();
        $this->_execSysPlugin('init');

        $this->_setApplicationContext();

        $this->_execSysPlugin('afterParseRoutes');
        if (is_null($this->_frameworkCache)) {
            $this->_frameworkCache = $this->inject($this->context['__ROUTE_CONFIG__']['frameworkCache']);
        }
        if (is_null($this->_rpcPublish)) {
            $this->_rpcPublish = $this->_frameworkCache->get($this->_rpcPublishCacheId);
        }
        if (isset($this->_rpcPublish[$this->_request['class'][$this->_request['method']]])) {
            throw new RPCException('rpc method is not found.', -1);
        }

        $this->_monitoringAspectp();

        $this->_processModelClass();

        if ($isHookApi) {//hook api标识
            if (count($this->_aryHookApiUri) > 0) {
                $this->context = array_pop($this->_aryHookApiUri);
            }
            if (count($this->_aryHookApiUri) == 0) {
                unset($this->_aryHookApiUri);
                $this->_aryHookApiUri = null;
            }
            return $this->_response($this->_currentBuffer, true);
        } else {
            echo $this->_response($this->_currentBuffer);
        }
        //die(var_dump($this->_data));
        //$this->_checkResourcesOccupation();
        //$this->_data = null;
        //unset($this->_data);
    }

    private function _parseParam($isHookApi) {
        $_contentType = 'application/json';

        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] != '') {
            $_contentType = $_SERVER['CONTENT_TYPE'];
            if (($_cut = strpos($_contentType, ';')) !== false) {
                $_contentType = substr($_contentType, 0, $_cut);
            }
        }
        switch ($_contentType) {
            case 'application/json' :
                $this->_requestType = 'json';
                break;
            case 'application/xml' :
                $this->_requestType = 'xml';
                break;
            default :
                $this->_httpStatus(415);
                break;
        }
        if ($isHookApi && isset($this->context['__PHP_INPUT__'])) {
            $_request = $this->context['__PHP_INPUT__'];
            $this->context['__PHP_INPUT__'] = null;
            unset($this->context['__PHP_INPUT__']);
        } else {
            $_request = file_get_contents('php://input');
        }
        if (empty($_request)) {
            $this->_httpStatus(403);
        }
        switch ($this->_requestType) {
            case 'json' :
                $this->_request = json_decode($_request, true);
                break;
            case 'xml' :
                $this->_request = XML2Array::createArray($_request);
                break;
        }
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
     * @throws \Phoenix\Exception\FormatException
     */
    private function _processModelClass() {
        //rpc也可以使用拦截器 psr-4
        $this->context['__INTERCEPTORS_CONFIG_PATH__'] = APP_PATH
            . $this->context['__CORE_MAPPING_PATH__'] . DIRECTORY_SEPARATOR . 'interceptors.config.php';
        if (is_file($this->context['__INTERCEPTORS_CONFIG_PATH__'])) {
            $this->context['__INTERCEPTORS__'] = require_once $this->context['__INTERCEPTORS_CONFIG_PATH__'];
            if (true !== ($_ic = parent::_worker(
                isset($this->_injectMapping[$this->_request['class']]['interceptor'])
                    ? $this->_injectMapping[$this->_request['class']]['interceptor']
                    : null
            )) && !is_null($_ic)) {
                echo $this->_response(10002);
                exit;
            }
        }

//        logger::debug($this->_request);
        if (empty($this->_request['parameters'])) {
            $this->_request['parameters'] = null;
        }
        $this->_currentBuffer = self::$_instances->invoke($this->_request['class'],
            $this->_request['method'],
            $this->_request['parameters']); //参数传入

        if (count($this->_interceptorChain) > 0) {
            foreach ($this->_interceptorChain as $_handler) {
                $_handler->afterCompletion($this->context);
            }
        }
    }

    private function _response($result, $direct = false) {
        $_response = array(
            'requestId' => $this->_request['requestId'],
            'error' => 0,
            'result' => $result
        );
        if (isset($this->_request['callback'])) {
            if (isset($this->_request['callback']['host'])) {
                $_callback = Client::create($this->_request['callback']['class'],
                    $this->_request['callback']['host']);
                $_method = $this->_request['callback']['method'];
                $_callback->async()->$_method($result);
            } else {
                $_response['callback'] = $this->_request['callback'];
            }
        }
        if ($direct) {
            return $_response;
        }
        switch ($this->_requestType) {
            case 'json' :
                return Helpers::jsonEncode($_response);
                break;
            case 'xml' :
                return Array2Xml::createXML($_response);
                break;
        }
    }

    /**
     * 加载配置
     */
    private function _setApplicationContext() {
        $this->context['__ARY_RPC_PATHS__'] = explode('\\', $this->_request['class']);
        $this->context['__CORE_MAPPING_PATH__'] = strtolower($this->context['__ARY_RPC_PATHS__'][0])
            . DIRECTORY_SEPARATOR . $this->context['__ARY_RPC_PATHS__'][1];
    }

    public static function process(Array & $context = array(), $isHookApi = false) {
        if (is_null(static::$_instances)) {
            static::$_instances = new self();
        }
        return static::$_instances->handler($context, $isHookApi);
    }

}
