<?php

namespace Phoenix\Cache\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Cache\Store;
use Phoenix\Cache\MemcachedHandler;
use Phoenix\Support\MsgHelper;
use Phoenix\Exception\FormatException;

/**
 * memcached 访问器
 */
class MemcachedStoreImpl implements Store {

    private $_handler = null;
    private $_hosts = null;
    private $_salt = null; //加入salt，避免在memcache中不同项目key一致
    private $_mode = null;

    public function __construct($hosts, $salt, $mode) {
        $this->_hosts = $hosts;
        $this->_mode = $mode;
        $this->_salt = $salt;
    }

    public function open() {
        $this->_handler = MemcachedHandler::create($this->_hosts, $this->_salt, $this->_mode);
        return true;
    }

    public function set($key, $var, $expires) {
        return $this->_handler->set($key, $var, $expires);
    }

    public function get($key, $expires) {
        return $this->_handler->get($key);
    }

    public function exists($key) {
        return $this->get($key, null) === false ? false : true;
    }

    public function delete($key) {
        if (is_array($key) && count($key) > 0) {
            foreach ($key as $v) {
                $this->_handler->delete($v);
            }
            return true;
        } else {
            return $this->_handler->delete($key);
        }
    }

    /**
     * memcache会自动清理已超时
     * $this->_handler->flush()如果使用会将所有缓存清空
     * @return boolean
     */
    public function gc() {
        return $this->_handler->flush();
    }

}
