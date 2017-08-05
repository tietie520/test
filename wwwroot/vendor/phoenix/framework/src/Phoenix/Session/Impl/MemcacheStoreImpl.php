<?php

namespace Phoenix\Session\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Session\Store;
use Memcache;

/**
 * memcache session访问器
 */
class MemcacheStoreImpl implements Store {

    private $_createTimeout;
    private $_timeout;
    private $_handler = null;
    private $_host = null;
    private $_port = null;

    public function __construct($hosts) {
        $this->_host = $hosts['memcache'][0];
        $this->_port = $hosts['memcache'][1];
    }

    public function open($createTimeout, $timeout) {
        $this->_createTimeout = $createTimeout;
        $this->_timeout = $timeout;
        $this->_handler = new Memcache();
        return $this->_handler->pconnect($this->_host, $this->_port);
    }

    public function set($realSessionId, Array $data = null) {
        return $this->_handler->set($realSessionId, $data,
            MEMCACHE_COMPRESSED, $this->_timeout);
    }

    public function activity($realSessionId) {
        $_flag = null;
        if (false !== ($_flag = $this->get($realSessionId))) {
            return $this->_handler->replace($realSessionId, $_flag,
                MEMCACHE_COMPRESSED, $this->_timeout);
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
