<?php

namespace Phoenix\Encryption;

if (!defined('IN_PX'))
    exit;

/**
 * AES双向加密
 */
class AES {

    public static $key = 'fdasdifela##$*!fdajKJFEiw'; //AES加密密钥 请及时配置，不要与其他项目相同

    /**
     * 加密
     * @param $value 加密数据
     * @param string $cipherAlg 模式：MCRYPT_RIJNDAEL_128
     * @return string
     */
    public static final function encrypt($value, $cipherAlg = MCRYPT_RIJNDAEL_128) {
        $_key = substr(md5(self::$key), 0, 24);
        $_iv = substr(base64_encode($_key), 0, mcrypt_get_iv_size($cipherAlg, MCRYPT_MODE_CBC));
        return bin2hex(mcrypt_encrypt($cipherAlg, $_key, $value, MCRYPT_MODE_CBC, $_iv));
    }

    /**
     * 解密
     * @param $value
     * @param string $cipherAlg
     * @return string
     */
    public static final function decrypt($value, $cipherAlg = MCRYPT_RIJNDAEL_128) {
        $_key = substr(md5(self::$key), 0, 24);
        $_iv = substr(base64_encode($_key), 0, mcrypt_get_iv_size($cipherAlg, MCRYPT_MODE_CBC));
        return trim(mcrypt_decrypt($cipherAlg, $_key, pack('H*', $value), MCRYPT_MODE_CBC, $_iv));
    }

    /**
     * 来自uc的加密解密如果没有装mcrypt情况下可使用，否则应使用AES加密
     * @param $string
     * @param string $operation
     * @param null $key
     * @param int $expiry
     * @return string
     */
    public static final function authcode($string, $operation = 'DECODE', $key = null, $expiry = 0) {
        $_ckeyLength = 4;
        $key = md5(is_null($key) ? self::$key : $key);
        $_keya = md5(substr($key, 0, 16));
        $_keyb = md5(substr($key, 16, 16));
        $_keyc = $_ckeyLength ?
            (strcmp('DECODE', $operation) == 0 ?
                substr($string, 0, $_ckeyLength) :
                substr(md5(microtime()), -$_ckeyLength)) :
            '';

        $_cryptKey = $_keya . md5($_keya . $_keyc);
        $_keyLength = strlen($_cryptKey);

        $string = strcmp('DECODE', $operation) == 0 ?
            base64_decode(substr($string, $_ckeyLength)) :
            sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $_keyb), 0, 16) . $string;
        $_stringLength = strlen($string);

        $_result = '';
        $_box = range(0, 255);

        $_rndKey = array();
        for ($i = 0; $i <= 255; $i++) {
            $_rndKey[$i] = ord($_cryptKey[$i % $_keyLength]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $_box[$i] + $_rndKey[$i]) % 256;
            $_tmp = $_box[$i];
            $_box[$i] = $_box[$j];
            $_box[$j] = $_tmp;
        }

        for ($a = $j = $i = 0; $i < $_stringLength; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $_box[$a]) % 256;
            $_tmp = $_box[$a];
            $_box[$a] = $_box[$j];
            $_box[$j] = $_tmp;
            $_result .= chr(ord($string[$i]) ^ ($_box[($_box[$a] + $_box[$j]) % 256]));
        }

        if (strcmp('DECODE', $operation) == 0) {
            if ((substr($_result, 0, 10) == 0 ||
                    substr($_result, 0, 10) - time() > 0) &&
                substr($_result, 10, 16) == substr(md5(substr($_result, 26) . $_keyb), 0, 16)
            ) {
                return substr($_result, 26);
            } else {
                return '';
            }
        } else {
            return $_keyc . str_replace('=', '', base64_encode($_result));
        }
    }

}
