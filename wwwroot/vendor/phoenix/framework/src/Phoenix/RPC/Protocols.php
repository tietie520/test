<?php

namespace Phoenix\RPC;

if (!defined('IN_PX'))
    exit;

use Phoenix\Support\Array2XML;
use Phoenix\Support\XML2Array;
use Phoenix\Support\Helpers;
use Phoenix\Exception\FormatException;
use Phoenix\Log\Log4p as logger;

/**
 * 传输协议
 */
class Protocols {

    const VERSION = '0.0.1';

    const TRANSFER_TYPE = 'json'; //传输类型 json xml
    const BINARY = false; //是否使用二进制传输
    const STATISTICS = false; //是否开启统计

    public static function encode($request) {
        $_request = '';
        switch (self::TRANSFER_TYPE) {
            case 'json' :
                $_request = Helpers::jsonEncode($request);
                break;
            case 'xml' :
                $_request = Array2XML::createXML($request);
                break;
        }
        return $_request;
    }

    public static function decode($response, $curlError) {
        $_out = null;
        switch ($curlError) {
            case 0 :
            case 28 :
                if (empty($response) || $response == '') {
                    return $response;
                }
                switch (self::TRANSFER_TYPE) {
                    case 'json' :
                        $_out = json_decode($response, true);
                        break;
                    case 'xml' :
                        $_out = XML2Array::createArray($response);
                        break;
                }
                $_out['error'] = $curlError;
                if (!isset($_out['requestId'])) {
                    logger::error($response);
                }
                break;
            default :
                logger::error($response);
                break;
        }
        return $_out;
    }

}
