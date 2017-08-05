<?php

namespace Phoenix\Cache\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Cache\Store;
use Memcache;

/**
 * memcache 访问器
 */
class MemcacheStoreImpl implements Store {

    private $_handler = null;
    private $_host = null;
    private $_port = null;
    private $_salt = null; //加入salt，避免在memcache中不同项目key一致

    public function __construct($hosts, $salt) {
        $this->_host = $hosts['memcache'][0];
        $this->_port = $hosts['memcache'][1];
        $this->_salt = $salt;
    }

    public function open() {
        $this->_handler = new Memcache();
        $_r = $this->_handler->pconnect($this->_host, $this->_port);
        return $_r;
    }

    public function set($key, $var, $expires) {
        $key = md5($key . $this->_salt);
        return $this->_handler->set($key, $var, MEMCACHE_COMPRESSED, $expires);
    }

    public function get($key, $expires) {
        return $this->_handler->get(md5($key . $this->_salt));
    }

    public function exists($key) {
        return $this->get($key, null) === false ? false : true;
    }

    public function delete($key) {
        if (is_array($key) && count($key) > 0) {
            foreach ($key as $v) {
                $this->_handler->delete(md5($v . $this->_salt));
            }
            return true;
        } else {
            return $this->_handler->delete(md5($key . $this->_salt));
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
