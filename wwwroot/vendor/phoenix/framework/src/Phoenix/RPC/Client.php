<?php

namespace Phoenix\RPC;

if (!defined('IN_PX'))
    exit;

use Phoenix\Routing\ProxyFactory;
use Phoenix\Support\MsgHelper;
use Phoenix\Exception\FormatException;
use Phoenix\Support\Helpers;
use Phoenix\Log\Log4p as logger;

/**
 * Json RPC 不走路由查找，比 Rest Api 性能高。除了调用方式不同，与`HttpHandler`极为相似
 * 如果需要获取更高性能需要用 socket
 */
class Client {

    const VERSION = '0.0.1';
//    const TIME_OUT = 20;

    private $_rpcServerIp = 'http://127.0.0.1:8000';
    private $_localMethod = false; //是否为本地方法
    private static $instances = null;
    private $_rpcServiceClassName = null;
    private $_async = false;
    private $_callback = null;
    private $_batchRequest = array();
    private $_request = array();
    private $_response = array();
    private $_proxy = null;
    private $_proxyUserPwd = null;
    private $_batch = false;

    protected function __construct($rpcServiceClassName, $rpcServerIp) {
        $this->_rpcServiceClassName = $rpcServiceClassName;
        if (!is_null($rpcServerIp)) {
            $this->_rpcServerIp = $rpcServerIp;
        }
        if (strpos($this->_rpcServerIp, '//127.0.0.1') !== false) {
            $this->_localMethod = true;
        }
    }

    public static function create($rpcServiceClassName, $rpcServerIp = null) {
        if (!isset(static::$instances[$rpcServiceClassName])) {
            static::$instances[$rpcServiceClassName] = new self($rpcServiceClassName, $rpcServerIp);
        }
        return static::$instances[$rpcServiceClassName];
    }

    /**
     * 设置代理ip访问
     * @param $ip
     * @param null $userPwd
     * @return $this
     */
    public function proxy($ip, $userPwd = null) {
        $this->_proxy = $ip;
        if (!is_null($userPwd)) {
            $this->_proxyUserPwd = $userPwd;
        }
        return $this;
    }

    public function getResult($requestId) {
        if (isset($this->_response[$requestId])) {
            return $this->_response[$requestId];
        }
        return null;
    }

    /**
     * 进行异步调用
     * 注：本次调用不直接使用结果，请求发出后立即返回
     * 由rpc server异步通知rpc client中设置的回调方法
     * @return $this
     */
    public function async() {
        $this->_async = true;
        return $this;
    }

    /**
     * 开启批量任务
     * 本地调用不会触发批量任务
     * @param bool $keepAsync 强行开启批量异步
     * @return $this
     */
    public function batch($keepAsync = false) {
        $this->_batch = false;
        if (false === $this->_localMethod || $keepAsync) {
            $this->_batch = true;
        }
        return $this;
    }

    /**
     * 执行批量任务
     */
    public function commit() {
        if ($this->_localMethod && false === $this->_batch) {//本地调用同时未开启batch必须同步执行
            return true;
        }
        if (false === $this->_batch) {
            throw new FormatException(MsgHelper::get('0x00003001'),
                '0x00003001');
        }
        $this->_batch = false;
//        logger::debug($this->_batchRequest);
        if (count($this->_batchRequest) > 0) {
            $_mh = curl_multi_init();
            $_handles = array();

            foreach ($this->_batchRequest as $_id => $_request) {
                $_handles[$_id] = $this->_curlInit($this->_rpcServerIp, $_request);
                curl_multi_add_handle($_mh, $_handles[$_id]);
            }

            $_active = null;

            do {
                $_mrc = curl_multi_exec($_mh, $_active);
            } while ($_mrc == CURLM_CALL_MULTI_PERFORM);

            while ($_active && $_mrc == CURLM_OK) {
                if (curl_multi_select($_mh) === -1) {
                    usleep(100);
                }
                do {
                    $_mrc = curl_multi_exec($_mh, $_active);
                } while ($_mrc == CURLM_CALL_MULTI_PERFORM);
            }

            //获取批处理内容
            foreach ($_handles as $_id => $_ch) {
                $_result = Protocols::decode(curl_multi_getcontent($_ch), curl_errno($_ch));
                $this->_pushResult($_result);
            }

            //移除批处理句柄
            foreach ($_handles as $_ch) {
                curl_multi_remove_handle($_mh, $_ch);
            }

            //关闭批处理句柄
            curl_multi_close($_mh);

            $this->_batchRequest = array();
        }
    }

    /**
     * 标识本次调用的回调方法
     * @param $class
     * @param $method
     * @return $this
     */
    public function callback($class, $method) {
        $this->_callback = array(
            'class' => $class,
            'method' => $method
        );
        //异步通知说明客户端请求不接收返回值，则需要通知服务端调用客户端的rpc进行回调
        //需要设置客户端的ip地址（注：此时客户端服务端的角色对调了）
        if ($this->_async) {
            $this->_callback['host'] = $_SERVER['HTTP_HOST'];
        }
        return $this;
    }

    public function __call($method, $arguments) {
        //未强行开启批量异步 或者 本地调用及非异步
        if ($this->_localMethod && (false === $this->_batch || false === $this->_async)) {
            $_result = Server::$_instances->invoke($this->_rpcServiceClassName,
                $method, $arguments);
            if (!is_null($this->_callback)) {//同步回调
                $_result = Server::$_instances->invoke($this->_callback['class'],
                    $this->_callback['method'],
                    $_result);
                $this->_callback = null;
            }
            return $_result;
        }

        $_requestId = Helpers::guid();
        $this->_wrapRequest($_requestId, $method, $arguments);

        if ($this->_batch) {
            $this->_batchRequest[$_requestId] = $this->_request[$_requestId];
            return $_requestId;
        }
        return $this->send($_requestId);
    }

    /**
     * @param $requestId
     * @return mixed
     */
    protected function send($requestId) {
        $_result = $this->_curl($this->_rpcServerIp,
            $this->_request[$requestId]);
        return $this->_pushResult($_result);
    }

    /**
     * 封装本次请求参数
     * @param $requestId
     * @param $method
     * @param $arguments
     * @return mixed
     */
    private function _wrapRequest($requestId, $method, $arguments) {
        $this->_request[$requestId] = array(
            'requestId' => $requestId,
            'class' => $this->_rpcServiceClassName,
            'method' => $method,
            'parameters' => $arguments,
            'async' => $this->_async,
            'callback' => $this->_callback
        );
        $this->_async = false;
        if (!is_null($this->_callback)) {
            $this->_callback = null;
        }
        return $this->_request[$requestId];
    }

    private function _pushResult($result) {
        if (isset($result['requestId'])) {
            switch ($result['error']) {
                case 0 :
                case 28 :
                    $this->_response[$result['requestId']] = $result['result'];
                    break;
                default :
                    break;
            }
            //同步回调
            if ($result['callback']) {
                //包裹成回调参数
                $_args = array($this->_response[$result['requestId']]);
                return Server::$_instances->invoke($result['callback']['class'],
                    $result['callback']['method'],
                    $_args);
            }
            return $this->_response[$result['requestId']];
        }
    }

    protected function _curlInit($url, $param) {
        $_async = $param['async'];
        unset($param['async']);

        $param = Protocols::encode($param);
        $_type = Protocols::TRANSFER_TYPE;

        $_ch = curl_init();
        $_options = array(
            CURLOPT_URL => $url . '/rpc',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array(
                "Content-Type: application/{$_type};charset=UTF-8",
                'Content-Length: ' . strlen($param)),
//            CURLOPT_SSL_VERIFYPEER => 0,
//            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $param,
        );
        if (!is_null($this->_proxy)) {
            $_options[CURLOPT_PROXY] = $this->_proxy;
            if (!is_null($this->_proxyUserPwd)) {
                $_options[CURLOPT_PROXYUSERPWD] = $this->_proxyUserPwd;
            }
        }
        if ($_async) {
            $_options[CURLOPT_TIMEOUT_MS] = 1;
        }

        curl_setopt_array($_ch, $_options);

        return $_ch;
    }

    protected function _curl($url, $param) {
        $_ch = $this->_curlInit($url, $param);
        $_result = Protocols::decode(curl_exec($_ch), curl_errno($_ch));
        curl_close($_ch);
        return $_result;
    }

}
