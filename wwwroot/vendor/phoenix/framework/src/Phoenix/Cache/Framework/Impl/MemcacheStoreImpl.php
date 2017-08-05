<?php

namespace Phoenix\Cache\Framework\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Cache\Store;
use Memcache;

/**
 * memcache 访问器
 */
class MemcacheStoreImpl implements Store {

    private function __Repository() {}

    private function __Value($dsn) {}

    private $_handler = null;
    private $_salt = null; //加入salt，避免在memcache中不同项目key一致

    public function open() {
        if (is_null($this->_handler)) {
            $this->_salt = $this->dsn['default']['dbName'] . 'fmt';
            $this->_handler = new Memcache();
            $this->_handler->pconnect($this->dsn['default']['memServers']['memcache'][0],
                $this->dsn['default']['memServers']['memcache'][1]);
        }
        return true;
    }

    public function set($key, $var, $expires) {
        $this->open();
        $key = md5($key . $this->_salt);
        return $this->_handler->set($key, $var, MEMCACHE_COMPRESSED, $expires);
    }

    public function get($key, $expires) {
        $this->open();
        return $this->_handler->get(md5($key . $this->_salt));
    }

    public function exists($key) {
        $this->open();
        return $this->get($key, null) === false ? false : true;
    }

    public function delete($key) {
        $this->open();
        if (is_array($key) && count($key) > 0) {
            foreach ($key as $v) {
                $this->_handler->delete(md5($v . $this->_salt));
            }
            return true;
        } else {
            return $this->_handler->delete(md5($key . $this->_salt));
        }
    }

    public function gc() {
        $this->open();
        return $this->_handler->flush();
    }

}
