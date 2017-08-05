<?php

namespace Phoenix\Session\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Session\Store;
use Phoenix\Support\Helpers;

/**
 * mysql session 访问器
 * session 的mysql内存表实现
 */
class DatabaseStoreImpl implements Store {

    private $_db;
    private $_createTimeout;
    private $_timeout;

    public function __construct(& $pdo) {
        $this->_db = & $pdo;
    }

    public function open($createTimeout, $timeout) {
        $this->_createTimeout = $createTimeout;
        $this->_timeout = $timeout;
    }

    public function set($realSessionId, Array $data = null) {
        $_time = microtime(true);
        //$this->_db->debug();
        return $this->_db->nonCacheable()->table('`#@__@sessions`')
            ->row(array(
                '`session_id`' => '?',
                '`create_time`' => '?',
                '`expires`' => '?',
                '`value`' => '?'
            ))
            ->bind(array(
                $realSessionId,
                intval($_time),
                $_time,
                Helpers::jsonEncode($data)
            ))
            ->replaceInto();
    }

    public function activity($realSessionId) {
        $_time = microtime(true);
        //$this->_db->debug();
        if ($this->_db->nonCacheable()->table('`#@__@sessions`')
            ->row(array(
                '`expires`' => $_time
            ))
            ->where('`session_id` = ? AND `create_time` > ? AND `expires` > ?')->bind(array(
                $realSessionId,
                (intval($_time) - $this->_createTimeout),
                ($_time - $this->_timeout)
            ))
            ->update()
        ) {
            return true;
        }
        return false;
    }

    public function get($realSessionId) {
        return json_decode($this->_db->nonCacheable()->field('`value`')
            ->table('`#@__@sessions`')
            ->where('`session_id` = ?')
            ->bind(array($realSessionId))
            ->find(),
            true);
    }

    public function destory($realSessionId) {
        return $this->_db->nonCacheable()->table('`#@__@sessions`')
            ->where('`session_id` = ?')
            ->bind(array($realSessionId))
            ->delete();
    }

    public function gc() {
        $_time = microtime(true);
        $this->_db->nonCacheable()->table('`#@__@sessions`')
            ->where('`create_time` < ? AND `expires` < ?')
            ->bind(array(
                (intval($_time) - $this->_createTimeout),
                ($_time - $this->_timeout)
            ))
            ->delete();
    }

}
