<?php

namespace Phoenix\Support;

if (!defined('IN_PX'))
    exit;

use HTMLPurifier;
use HTMLPurifier_Config;
use Phoenix\Log\Log4p as logger;

/**
 * HTMLPurifier
 */
class Purifier {

    /**
     * HTMLPurifier 封装,最安全的防止xss方式
     * 在需要入库的参数调用此函数
     * @param $content
     * @return array|string
     */
    public static function html($content) {
        $_options = array(
            //允许属性 div table tr td br元素
            array('HTML.AllowedElements',
                array(
                    'div' => true,
                    'table' => true,
                    'tr' => true,
                    'td' => true,
                    'br' => true,
                    'img' => true,
                    'p' => true,
                    'i' => true,
                    'b' => true,
                    'strong' => true,
                    'a' => true
                ),
                false
            ),
            //允许属性 class
            array('HTML.AllowedAttributes', array(
                'class' => true,
                'href' => true,
                'src' => true,
                'alt' => true,
                'id' => true,
                'style' => true,
            ), false),
            //禁止classes如
            array('Attr.ForbiddenClasses', array('resume_p' => true), false),
            //去空格
            array('AutoFormat.RemoveEmpty', true, false),
            //去nbsp
            array('AutoFormat.RemoveEmpty.RemoveNbsp', true, false),
//            array('URI.Disable', true, false),
        );
        $_config = HTMLPurifier_Config::createDefault();
        $_config->autoFinalize = false;
//        $_config->set('Code.Encoding', 'UTF-8');
//        $_config->set('HTML.Doctype', 'XHTML 1.0 Transitional');
        foreach ($_options as $_option) {
            $_config->set($_option[0], $_option[1], $_option[2]);
        }
        $purifier = HTMLPurifier::instance($_config);
        $purifier->config->set('Cache.SerializerPath', CACHE_PATH);
        if (!is_array($content)) {
            return $purifier->purify(urldecode($content));
        }
        foreach ($content as $_k => $_v) {
            $content[$_k] = $purifier->purify(urldecode($_v));
        }
        return $content;
    }

    /**
     * 清除xss一种方式 推荐使用Html Purifier
     * @param $string
     * @param bool $low
     * @return bool
     */
    public static function cleanXss(&$string, $low = false) {
        if (!is_array($string)) {
            $string = trim($string);
            $string = strip_tags($string);
            $string = htmlspecialchars($string);
            if ($low) {
                return true;
            }
            $string = str_replace(array('"', "\\", "'", "..", "../", "./", "//"), '', $string);
            $_no = '/%0[0-8bcef]/';
            $string = preg_replace($_no, '', $string);
            $_no = '/%1[0-9a-f]/';
            $string = preg_replace($_no, '', $string);
            $_no = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';
            $string = preg_replace($_no, '', $string);
            return true;
        }
        $keys = array_keys($string);
        foreach ($keys as $key) {
            cleanXss($string[$key]);
        }
    }

}
