<?php

namespace Phoenix\Cache;

if (!defined('IN_PX'))
    exit;

/**
 * CacheStore接口
 */
interface Store {

    public function open();

    public function set($key, $var, $expires);

    public function get($key, $expires);

    public function delete($key);

    public function gc();
}
