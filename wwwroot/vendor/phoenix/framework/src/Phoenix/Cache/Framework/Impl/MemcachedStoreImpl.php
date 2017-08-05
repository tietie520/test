<?php

namespace Phoenix\Cache\Framework\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Cache\Store;

/**
 * Memcached 访问器
 */
class MemcachedStoreImpl implements Store {

    private function __Repository() {}

    private function __Value($dsn) {}

    private $_handler = null;

    public function open() {
        if (is_null($this->_handler)) {
            $this->_handler = MemcachedHandler::create($this->dsn['default']['memServers'],
                $this->dsn['default']['dbName'] . 'fmt', $this->dsn['default']['cacheType']);
        }
        return true;
    }

    public function set($key, $var, $expires) {
        $this->open();
        return $this->_handler->set($key, $var, $expires);
    }

    public function get($key, $expires) {
        $this->open();
        return $this->_handler->get($key);
    }

    public function exists($key) {
        $this->open();
        return $this->get($key, null) === false ? false : true;
    }

    public function delete($key) {
        $this->open();
        if (is_array($key) && count($key) > 0) {
            foreach ($key as $v) {
                $this->_handler->delete($v);
            }
            return true;
        } else {
            return $this->_handler->delete($key);
        }
    }

    public function gc() {
        $this->open();
        return $this->_handler->flush();
    }

}
