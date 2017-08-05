<?php

namespace Phoenix\Support;

if (!defined('IN_PX'))
    exit;

class Curl {

    public static final function http($url, $data = null) {
        $_ch = curl_init();
        curl_setopt($_ch, CURLOPT_URL, $url);
        if (!is_null($data)) {
            curl_setopt($_ch, CURLOPT_PORT, 1);
            curl_setopt($_ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
        $_output = curl_exec($_ch);
        curl_close($_ch);
        return $_output;
    }

    public static final function https($url, $data = null) {
        $_ch = curl_init();
        curl_setopt($_ch, CURLOPT_URL, $url);
        curl_setopt($_ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($_ch, CURLOPT_SSL_VERIFYHOST, false);
        if (!is_null($data)) {
            curl_setopt($_ch, CURLOPT_PORT, 1);
            curl_setopt($_ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
        $_output = curl_exec($_ch);
        curl_close($_ch);
        return $_output;
}

}
