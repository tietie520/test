<?php

namespace Phoenix\Api;

if (!defined('IN_PX'))
    exit;

use Phoenix\Support\Array2XML;
use Phoenix\Support\XML2Array;
use Phoenix\Support\Helpers;
use Phoenix\Support\Purifier;
use Phoenix\Exception\FormatException;
use Phoenix\Log\Log4p as logger;

/**
 * 传输协议
 */
class Protocols {

    const VERSION = '0.0.1';

    static $transferType = 'json'; //传输类型 json xml
    const BINARY = false; //是否使用二进制传输
    const STATISTICS = false; //是否开启统计

    public static function pack($request) {
        $_request = '';
        switch (self::$transferType) {
            case 'json' :
                $_request = Helpers::jsonEncode($request);
                break;
            case 'xml' :
                $_request = Array2XML::createXML($request);
                break;
        }
        return $_request;
    }

    public static function unpack() {
        $_out = null;
        if (isset($_POST['__PHP_INPUT__'])) {
            $_response = $_POST['__PHP_INPUT__'];
        } else {
            $_response = file_get_contents('php://input');
        }
        $_response = Purifier::html($_response);
        switch (self::$transferType) {
            case 'json' :
                $_out = json_decode($_response, true);
                break;
            case 'xml' :
                $_out = XML2Array::createArray($_response);
                break;
        }
        return $_out;
    }

}
