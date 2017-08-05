<?php

namespace Phoenix\Api;

if (!defined('IN_PX'))
    exit;

use Phoenix\Support\MsgHelper;
use Phoenix\Exception\FormatException;
use Phoenix\Support\Helpers;
use Phoenix\Log\Log4p as logger;

/**
 * api Gateway
 */
class Gateway {

    const VERSION = '0.0.1';

    const TIME_OUT = 20;

    private $_apiServerUrl = 'http://127.0.0.1:8000';
    private static $instances = null;
    private $_path = null;
    private $_ssl = null;
    private $_batchRequest = array();
    private $_request = array();
    private $_response = array();
    private $_proxy = null;
    private $_proxyUserPwd = null;
    private $_batch = false;
    private $_header = null;

    protected function __construct($apiServerUrl) {
        $this->_apiServerUrl = $apiServerUrl;
        if (strpos($this->_apiServerUrl, 'https://') === 0) {
            $this->_ssl = true;
        }
    }

    public static function create($apiServerUrl) {
        if (!isset(static::$instances[$apiServerUrl])) {
            static::$instances[$apiServerUrl] = new self($apiServerUrl);
        }
        return static::$instances[$apiServerUrl];
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
     * 开启批量任务
     * 本地调用不会触发批量任务
     * @return $this
     */
    public function batch() {
        $this->_batch = false;
        return $this;
    }

    /**
     * 执行批量任务
     */
    public function commit() {
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
                $_handles[$_id] = $this->_curlInit($_request);
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
                $this->_response[$_id] = curl_multi_getcontent($_ch);
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

    public function path($path) {
        $this->_path = $path;
        return $this;
    }

    private function _action($requestId) {
        if ($this->_batch) {
            $this->_batchRequest[$requestId] = $this->_request[$requestId];
            return $requestId;
        }
        return $this->_curl($this->_request[$requestId]);
    }

    public function get() {
        return $this->_action($this->_wrapRequest());
    }

    /**
     * @param null $post
     * @return mixed
     */
    public function post($post = null) {
        $_requestId = $this->_wrapRequest();
        $this->_request[$_requestId]['post'] = $post;
        return $this->_action($_requestId);
    }

    /**
     * @param array $json
     * @param string $charset
     * @return mixed
     */
    public function json(Array $json, $charset = 'UTF-8') {
        Protocols::$transferType = 'json';
        $_json = Protocols::pack($json);
        return $this->header(array(
            "Content-Type: application/json;charset={$charset}",
            'Content-Length: ' . strlen($_json)
        ))->post($_json);
    }

    /**
     * 封装本次请求参数
     * @return mixed
     */
    private function _wrapRequest() {
        $_requestId = Helpers::guid();
        $this->_request[$_requestId] = array(
            'requestId' => $_requestId,
            'path' => $this->_path,
            'header' => $this->_header
        );
        $this->_path = null;
        $this->_header = null;
        return $_requestId;
    }

    public function header($header) {
        $this->_header = $header;
        return $this;
    }

    protected function _curlInit($request) {
        $_ch = curl_init();
        $_options = array(
            CURLOPT_URL => $this->_apiServerUrl . $request['path'],
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_CONNECTTIMEOUT => self::TIME_OUT,
        );
        if (isset($request['header'])) {
            $_options[CURLOPT_HTTPHEADER] = $request['header'];
        }
        if (isset($this->_ssl)) {
            $_options[CURLOPT_SSL_VERIFYPEER] = 0;
            $_options[CURLOPT_SSL_VERIFYHOST] = 0;
        }
        if (isset($request['post'])) {
            $_options[CURLOPT_POST] = 1;
            $_options[CURLOPT_POSTFIELDS] = $request['post'];
        }
        if (!is_null($this->_proxy)) {
            $_options[CURLOPT_PROXY] = $this->_proxy;
            if (!is_null($this->_proxyUserPwd)) {
                $_options[CURLOPT_PROXYUSERPWD] = $this->_proxyUserPwd;
            }
        }

        curl_setopt_array($_ch, $_options);

        return $_ch;
    }

    protected function _curl($request) {
        $_ch = $this->_curlInit($request);
        $this->_response[$request['requestId']] = curl_exec($_ch);
        curl_close($_ch);
        return $this->_response[$request['requestId']];
    }

}
