<?php

namespace Phoenix\Session\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Session\Store;
use Phoenix\Cache\MemcachedHandler;
use Phoenix\Support\MsgHelper;
use Phoenix\Exception\FormatException;

/**
 * memcached session访问器
 */
class MemcachedStoreImpl implements Store {

    private $_createTimeout;
    private $_timeout;
    private $_handler = null;
    private $_hosts = null;
    private $_salt = null;
    private $_mode = null;

    public function __construct($hosts, $salt, $mode) {
        $this->_hosts = $hosts;
        $this->_mode = $mode;
        $this->_salt = $salt;
    }

    public function open($createTimeout, $timeout) {
        $this->_createTimeout = $createTimeout;
        $this->_timeout = $timeout;

        $this->_handler = MemcachedHandler::create($this->_hosts, $this->_salt, $this->_mode);

        return true;
    }

    public function set($realSessionId, Array $data = null) {
        return $this->_handler->set($realSessionId, $data, $this->_timeout);
    }

    public function activity($realSessionId) {
        $_flag = null;
        if (false !== ($_flag = $this->get($realSessionId))) {
            return $this->_handler->replace($realSessionId, $_flag, $this->_timeout);
        }
        return false;
    }

    public function get($realSessionId) {
        return $this->_handler->get($realSessionId);
    }

    public function destory($realSessionId) {
        return $this->_handler->delete($realSessionId);
    }

    /**
     * memcache会自动清理已超时
     * $this->_handler->flush()如果使用会讲所有缓存清空
     * */
    public function gc() {
        return true;
    }

}
