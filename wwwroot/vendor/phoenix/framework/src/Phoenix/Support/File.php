<?php

namespace Phoenix\Support;

if (!defined('IN_PX'))
    exit;

class File {

    /**
     * 判断缓存是否存在或者过期 默认缓存1000秒
     * @param $cacheId
     * @param int $expires
     * @param string $root
     * @return bool
     */
    public static function isValid($cacheId, $expires = 1000, $root = CACHE_PATH) {
        @clearstatcache();
        if (!is_file($root . $cacheId) || (false === ($_mtime = @filemtime($root . $cacheId))))
            return false;
        return ($_mtime + $expires) < microtime(true) ? false : true;
    }

    public static function exists($cacheId, $root = CACHE_PATH) {
        return is_file($root . $cacheId);
    }

    /**
     * 递归创建目录
     * @param type $dir
     * @return type
     */
    public static function mkdir($dir, $root = CACHE_PATH) {
        if (!is_dir($root)) {
            @mkdir($root, 0777);
            @chmod($root, 0777);
        }

        return is_dir($root . $dir) || (self::mkdir(dirname($dir), $root) &&
            @mkdir($root . $dir, 0777) && @chmod($root . $dir, 0777));
    }

    /**
     * 写入缓存
     * @param $cacheId
     * @param $cacheContent
     * @param int $flags
     * @param string $root
     * @return bool|string
     */
    public static function write($cacheId, $cacheContent, $flags = LOCK_EX, $root = CACHE_PATH) {
        if (self::mkdir(dirname($cacheId), $root)) {
            $_file = $root . $cacheId;
            $_fpc = file_put_contents($_file, $cacheContent, $flags);
            @chmod($_file, 0777);
            return $_file;
        }
        return false;
    }

    /**
     * 获取缓存文件
     * @param $cacheId
     * @param string $root
     * @return string
     */
    public static function fetch($cacheId, $root = CACHE_PATH) {
        return file_get_contents($root . $cacheId);
    }

    /**
     * 如果超时删除缓存文件，全部使用默认值为清空全部缓存
     * 指定超时时间，则将已超时文件删除
     * @param int $expires
     * @param string $root
     * @return bool
     */
    public static function clearExpired($expires = 0, $root = CACHE_PATH) {
        if (false !== ($_cacheDir = @opendir($root))) {
            while (false !== ($_userFile = @readdir($_cacheDir))) {
                if ($_userFile != '.' && $_userFile != '..') {
                    $_cacheId = $root . $_userFile;
                    if ($expires == 0 || !self::isValid($_cacheId, $expires))
                        @unlink($_cacheId);
                }
            }
            @closedir($_cacheDir);
            return true;
        }
        return false;
    }

    /**
     * 读取目录下所有文件，返回一个数组
     * @param $path
     * @param array $_result
     * @return array
     */
    public static function readAllFiles($path, $_result = array()) {
        if (false !== ($_handle = @opendir($path))) {
            while (false !== ($_file = @readdir($_handle))) {
                if ($_file != '.' && $_file != '..') {
                    //echo($_file . '<br />');
                    if (is_dir($path . DIRECTORY_SEPARATOR . $_file))
                        $_result = self::readAllFiles($path . DIRECTORY_SEPARATOR . $_file, $_result);
                    else
                        $_result[] = $path . DIRECTORY_SEPARATOR . $_file;
                }
            }
            @closedir($_handle);
        }
        return $_result;
    }

    /**
     * 写入文件
     * @param $path
     * @param $contents
     * @return bool
     */
    public static function fileWrite($path, $contents) {
        if (false !== ($_fp = fopen($path, 'wb'))) {
            flock($_fp, LOCK_EX | LOCK_NB);
            $_tmp = fwrite($_fp, $contents);
            flock($_fp, LOCK_UN);
            fclose($_fp);
            if ($_tmp === false) {
                return false;
            }
            @chmod($path, 0777);
            return true;
        }
        return false;
    }

    /**
     * 删除文件
     * @param $fileName
     * @param string $path
     * @return bool
     */
    public static function delete($fileName, $path = CACHE_PATH) {
        if (is_array($fileName)) {
            if (count($fileName) > 0) {
                foreach ($fileName as $v) {
                    if (is_file($path . $v)) {
                        @unlink($path . $v);
                    }
                }
            }
            return true;
        } else {
            if (is_file($path . $fileName)) {
                return @unlink($path . $fileName);
            }
            return false;
        }
    }

    /**
     * 删除目录
     * @param string $dirName
     * @param string $path
     * @return bool
     */
    public static function rmdir($dirName = '', $path = CACHE_PATH) {
        if ($dirName != '') {
            $dirName = ltrim($dirName, DIRECTORY_SEPARATOR);
        }
        if (is_dir($path . $dirName) &&
            false !== ($_handle = @opendir($path . $dirName))) {
            while (false !== ($_file = @readdir($_handle))) {
                if ($_file != '.' && $_file != '..') {
                    $_dir = $dirName . DIRECTORY_SEPARATOR . $_file;
                    is_dir($path . $_dir) ?
                        self::rmdir($_dir, $path) :
                        @unlink($path . $_dir);
                }
            }
            @closedir($_handle);
            return rmdir($path . $dirName);
        }
        return false;
    }

}
