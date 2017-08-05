<?php

namespace App\Tools;

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

    public static final function httpPostXml($url, $xmlData) {
        $_ch = curl_init();
        $_header[] = 'Content-type: text/xml'; //定义content-type为xml
        curl_setopt($_ch, CURLOPT_URL, $url); //定义表单提交地址
        curl_setopt($_ch, CURLOPT_POST, 1);   //定义提交类型 1：POST ；0：GET
        curl_setopt($_ch, CURLOPT_HEADER, 1); //定义是否显示状态头 1：显示 ； 0：不显示
        curl_setopt($_ch, CURLOPT_HTTPHEADER, $_header);//定义请求类型
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 0);//定义是否直接输出返回流
        curl_setopt($_ch, CURLOPT_POSTFIELDS, $xmlData); //定义提交的数据，这里是XML文件
        $_output = curl_exec($_ch);
        curl_close($_ch);//关闭
        return $_output;
    }

}
