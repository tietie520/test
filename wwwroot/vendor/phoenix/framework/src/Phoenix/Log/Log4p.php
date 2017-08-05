<?php

namespace Phoenix\Log;

if (!defined('IN_PX'))
    exit;

use Phoenix\Support\File;

/**
 * Class Log4p
 * @package Tools
 */
class Log4p {

    /**
     * 写入日志
     * @param string $word
     * @param string $path
     * @return bool|string
     */
    public static final function write($word = '', $path = 'log.txt') {
        return File::write($path,
            '执行日期：' . strftime('%Y-%m-%d %H:%M:%S', time())
            . "\n" . print_r($word, true) . "\n",
            FILE_APPEND | LOCK_EX, LOG_PATH);
    }

    /**
     * 开发者调试
     * @param type $word
     * @param bool $cut
     * @return type
     */
    public static final function d($word, $cut = false) {
        return self::debug($word, $cut);
    }
    public static final function debug($word, $cut = false) {
        if (!PX_DEBUG) {
            return true;
        }
        $_path = $cut ? '-' . date('Y-m-d', time()) : '';
        return self::write($word, "debug{$_path}.txt");
    }

    /**
     * 输出信息，通常是程序自我监控级别
     * @param type $word
     * @param bool $cut
     * @return type
     */
    public static final function i($word, $cut = false) {
        return self::info($word, $cut);
    }
    public static final function info($word, $cut = false) {
        $_path = $cut ? '-' . date('Y-m-d', time()) : '';
        return self::write($word, "info{$_path}.txt");
    }

    /**
     * 输出警告
     * @param type $word
     * @param bool $cut
     * @return type
     */
    public static final function w($word, $cut = false) {
        return self::warn($word, $cut);
    }
    public static final function warn($word, $cut = false) {
        $_path = $cut ? '-' . date('Y-m-d', time()) : '';
        return self::write($word, "warn{$_path}.txt");
    }

    /**
     * 输入运行期错误
     * @param type $word
     * @param bool $cut
     * @return type
     */
    public static final function e($word, $cut = false) {
        return self::error($word, $cut);
    }
    public static final function error($word, $cut = false) {
        $_path = $cut ? '-' . date('Y-m-d', time()) : '';
        return self::write($word, "error{$_path}.txt");
    }

    /**
     * 输入致命错误
     * @param type $word
     * @param bool $cut
     * @return type
     */
    public static final function f($word, $cut = false) {
        return self::fatal($word, $cut);
    }
    public static final function fatal($word, $cut = false) {
        $_path = $cut ? '-' . date('Y-m-d', time()) : '';
        return self::write($word, "fatal{$_path}.txt");
    }

    /**
     * 删除文件
     * @param type $fileName
     * @return type
     */
    public static final function delete($fileName) {
        return File::delete($fileName, LOG_PATH);
    }

}
