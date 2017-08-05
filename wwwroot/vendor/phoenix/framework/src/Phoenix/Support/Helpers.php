<?php

namespace Phoenix\Support;

if (!defined('IN_PX'))
    exit;

use Phoenix\Log\Log4p as logger;

/**
 * Helper
 */
class Helpers {

    public static function guid() {
        $_uuid = '';
        if (function_exists('com_create_guid')) {
            $_uuid = com_create_guid();
        } else {
            $_charId = strtoupper(md5(uniqid(rand(), true)));
            $_hyphen = chr(45); // "-"
            $_uuid = chr(123)// "{"
                . substr($_charId, 0, 8) . $_hyphen
                . substr($_charId, 8, 4) . $_hyphen
                . substr($_charId, 12, 4) . $_hyphen
                . substr($_charId, 16, 4) . $_hyphen
                . substr($_charId, 20, 12)
                . chr(125); // "}"
        }
        return strtolower(trim($_uuid, '{}'));
    }

    public static function jsonEncode($value) {
        if (version_compare(PHP_VERSION,'5.4.0','<')) {
            $_str = json_encode($value);
            $_str = preg_replace_callback(
                "#\\\u([0-9a-f]{4})#i",
                function($matchs) {
                    return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
                },
                $_str
            );
            return $_str;
        }
        else {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
    }

}
