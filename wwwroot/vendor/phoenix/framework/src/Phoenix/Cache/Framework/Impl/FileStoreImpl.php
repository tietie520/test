<?php

namespace Phoenix\Cache\Framework\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Cache\Store;
use Phoenix\Support\File;

/**
 * file cache 访问器
 */
class FileStoreImpl implements Store {

    public function open() {
        return true;
    }

    public function set($key, $var, $expires = 0) {
        $var = var_export($var, true);
        $_cache = <<<EOF
<?php
if(!defined('IN_PX')) exit;
return {$var};
EOF;
        return File::write($key . $this->_ext, $_cache);
    }

    public function get($key, $expires = 0) {
        $key .= $this->_ext;
        if (File::exists($key)) {
            $_out = require_once CACHE_PATH . $key;
            return $_out;
        }
        return false;
    }

    public function exists($key) {
        return File::exists($key . $this->_ext);
    }

    public function delete($key) {
        if (is_array($key)) {
            foreach ($key as $_k => $_v) {
                $key[$_k] = $_v . $this->_ext;
            }
        } else {
            $key .= $this->_ext;
        }
        return File::delete($key);
    }

    /**
     * 磁盘上可清空缓存目录
     * */
    public function gc() {
        return File::rmdir();
    }

    private $_ext = '.cache.php'; //扩展名

}
