<?php

namespace Phoenix\PXPDO\Impl;

if (!defined('IN_PX'))
    exit;

use MongoClient;
use Phoenix\PXPDO\Mongo;

class MongoImpl implements Mongo {

    //持久层 value 别名 $value = ''
    //TODO
    private function __Repository() {}

    private function __Value($dsn) {}

    private $_mongoHandler = null;
    private $_dbHandler = null;
    private $_collections = array();

    public function db() {
        if ($this->_lazyInit()) {
            return $this->_dbHandler;
        }
        return false;
    }

    public function collections($_collections) {
        if (!isset($this->_collections[$_collections])) {
            if (false !== $this->_lazyInit()) {
                $_preCollections = $this->dsn['mongo']['prefix'] . $_collections;
                $this->_collections[$_collections] = $this->_dbHandler->$_preCollections;
            } else {
                return false;
            }
        }
        return $this->_collections[$_collections];
    }

    private function _lazyInit() {
        if (is_null($this->_mongoHandler)) {
            $_dsn = 'mongodb://';
            if ($this->dsn['mongo']['auth']) {
                $_dsn .= "{$this->dsn['mongo']['user']}:{$this->dsn['mongo']['password']}@";
            }
            $_dsn .= "{$this->dsn['mongo']['host']}:{$this->dsn['mongo']['port']}/{$this->dsn['mongo']['dbName']}";
            $this->_mongoHandler = new MongoClient($_dsn);
            $this->_dbHandler = $this->_mongoHandler->{$this->dsn['mongo']['dbName']};
        }
        return !!$this->_dbHandler;
    }

}
