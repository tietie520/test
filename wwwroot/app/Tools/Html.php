<?php

namespace App\Tools;

if (!defined('IN_PX'))
    exit;

class Html {

    /**
     * 入库，输入框进入数据库，替换成HTML
     * @param $str
     * @param string $length
     * @return string|void
     */
    public static function getTextToHtml($str, $length = '') {
        if (!isset($str))
            return;
        $str = htmlspecialchars(stripslashes(trim($str)), ENT_QUOTES);
        return $length && (strlen($str) > $length) ? self::getLenStr($str, $length) : $str;
    }

    /**
     * 出库，输出到输入框
     * 去除Html格式，用于显示输出
     * @param $str
     * @return string|void
     */
    public static function outputToText($str) {
        if (!isset($str))
            return;
        return stripslashes(str_replace(array('&amp;', '&lt;', '&gt;', '&quot;', '&#039;',
            '&nbsp;'), array('&', '<', '>', '"', '\'', ' '), trim($str)));
    }

    public static function strip($i) {
        return strip_tags(self::outputToText(str_replace(array(' ', '　'), array('', ''), $i)));
    }

    public static function stripJsonSlashes($value) {
        if ($value != '' && is_string($value)) {
            return str_replace(array('"', '\\', "\r", "\n"), array('&quot;', '&#92;', '', ''), stripslashes($value));
        }
        return $value;
    }

    public static function replaceKey($value, $key) {
        if (!$key) {
            return $value;
        }
        return str_replace($key, '<b style="color:red">' . $key . '</b>', $value);
    }

    public static function clearBreak($document) {
        return str_replace(array("\t", "\r\n", "\r", "\n", '　'), array('', '', '', '', ''), $document);
    }

    public static function nl2p($string, $lineBreaks = false) {
        if (empty($string))
            return $string;
        // Remove existing HTML formatting to avoid double-wrapping things
        $string = str_replace(array('<p>', '</p>', '<br>', '<br />'), '', $string);

        // It is conceivable that people might still want single line-breaks
        // without breaking into a new paragraph.
        if ($lineBreaks)
            return '<p>' . preg_replace(array("/([\n]{2,})/i", "/([^>])\n([^<])/i"),
                array("</p>\n<p>", '<br>'), trim($string)) . '</p>';
        else
            return '<p>' . preg_replace("/([\n]{1,})/i", "</p>\n<p>", trim($string)) . '</p>';
    }

    /**
     * 去除Html格式
     * @param $document
     * @return mixed
     */
    public static function htmlToText($document) {
        $search = array("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
            "'<[\/\!]*?[^<>]*?>'si", // 去掉 HTML 标记
            "'([\r\n])[\s]+'", // 去掉空白字符
            "'&(quot|#34);'i", // 替换 HTML 实体
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&#(\d+);'");  // 作为 PHP 代码运行
        $replace = array("",
            "",
            "\\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            "chr(\\1)");
        return preg_replace($search, $replace, strip_tags($document));
    }

    /**
     * 只显示指定数量的内容
     * @param $string
     * @param $sublen
     * @param int $start
     * @param string $code
     * @return string
     */
    public static function getLenStr($string, $sublen, $start = 0, $code = 'UTF-8') {
        if ($code == 'UTF-8') {
            $pa = '/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf]'
                . '[\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|'
                . '\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|'
                . '[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/';
            preg_match_all($pa, $string, $t_string);

            if (count($t_string[0]) - $start > $sublen)
                return join('', array_slice($t_string[0], $start, $sublen))
                . '...';
            return join('', array_slice($t_string[0], $start, $sublen));
        }
        else {
            $start = $start * 2;
            $sublen = $sublen * 2;
            $strlen = strlen($string);
            $tmpstr = '';

            for ($i = 0; $i < $strlen; $i++) {
                if ($i >= $start && $i < ($start + $sublen)) {
                    if (ord(substr($string, $i, 1)) > 129) {
                        $tmpstr .= substr($string, $i, 2);
                    } else {
                        $tmpstr .= substr($string, $i, 1);
                    }
                }
                if (ord(substr($string, $i, 1)) > 129)
                    $i++;
            }
            if (strlen($tmpstr) < $strlen)
                $tmpstr .= '...';
            return $tmpstr;
        }
    }

    /**
     * @param $controlValueObject
     * @param $controlName
     * @return string
     */
    public static function getControlValueObject($controlValueObject, $controlName) {
        return stripslashes($controlValueObject && is_object($controlValueObject) ?
            $controlValueObject->$controlName : $controlValueObject);
    }

    /**
     * @param $width
     * @return string
     */
    public static function getControlWidth($width) {
        return is_numeric($width) ? ' size="' . $width . '"' :
            ( $width ? ' style="width:' . $width . '"' : ' style="width:96%;"' );
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param string $width
     * @param string $className
     * @param string $max
     * @return string
     */
    public static function text($literal, $controlName, $controlValueObject = null,
                                $width = '', $className = '', $max = '') {
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        if ($literal) {
            $width = self::getControlWidth($width);

            if ($max)
                $max = ' maxlength="' . $max . '"';
            if ($className)
                $className = ' class="' . $className . '"';
            return '<input type="text" name="' . $controlName . '" id="'
            . $controlName . '" value="' . $_cvo . '"' . $width
            . $className . $max . ' />';
        }
        else {
            return $_cvo;
        }
    }

    public static function hidden($controlName, $controlValueObject = null) {
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        return '<input type="hidden" name="' . $controlName . '" id="'
        . $controlName . '" value="' . $_cvo . '" />';
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param string $width
     * @param string $className
     * @param string $max
     * @return string
     */
    public static function password($literal, $controlName, $controlValueObject = null,
                                    $width = '', $className = '', $max = '') {
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        if ($literal) {
            $width = self::getControlWidth($width);

            if ($max)
                $max = ' maxlength="' . $max . '"';
            if ($className)
                $className = ' class="' . $className . '"';
            return '<input type="password" name="' . $controlName . '" id="'
            . $controlName . '" value="' . $_cvo . '"'
            . $width . $className . $max . ' />';
        }
        else {
            return $_cvo;
        }
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param string $width
     * @param null $aryDefaultValue
     * @return string
     */
    public static function setDate($literal, $controlName, $controlValueObject = null,
                                   $width = '', $aryDefaultValue = null) {
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        if ($literal) {
            $width = self::getControlWidth($width);

            $aryDefaultValue = $aryDefaultValue ? $aryDefaultValue : array(0, 0);
            return '<input type="text" name="' . $controlName . '" id="'
            . $controlName . '" value="' . $_cvo . '"'
            . $width . ' class="set_date" onclick="SelectDate(this, '
            . $aryDefaultValue[0] . ', '
            . $aryDefaultValue[1] . ')" />';
        } else {
            return $_cvo;
        }
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param string $width
     * @param string $rows
     * @param string $className
     * @param string $js
     * @return string
     */
    public static function textarea($literal, $controlName, $controlValueObject = null,
                                    $width = '', $rows = '', $className = '', $js = '') {
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        if ($literal) {
            $width = self::getControlWidth($width);

            if ($rows)
                $rows = ' rows="' . $rows . '"';
            if ($className)
                $className = ' class="' . $className . '"';
            return '<textarea name="' . $controlName . '" id="'
            . $controlName . '"' . $width . $rows . $className . $js
            . '>' . self::outputToText($_cvo)
            . '</textarea>';
        }
        else {
            return $_cvo;
        }
    }

    public static function editor($literal, $controlName, $controlValueObject = null) {
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        if ($literal) {
            return '<textarea name="' . $controlName . '" id="'
            . $controlName . '">' . self::outputToText($_cvo)
            . '</textarea>';
        } else {
            return $_cvo;
        }
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param null $aryValue
     * @param null $selectDefaultValue
     * @param null $aryTopDefaultValue
     * @param string $width
     * @return string
     */
    public static function select($literal, $controlName, $controlValueObject = null,
                                  $aryValue = null, $selectDefaultValue = null, $aryTopDefaultValue = null, $width = '') {
        $_name = '';
        $_id = '';
        if (is_array($controlName)) {
            $_name = $controlName[0];
            $_id = $controlName[1];
        } else {
            $_name = $controlName;
            $_id = $controlName;
        }
        $_cvo = self::getControlValueObject($controlValueObject, $controlName);
        if ($literal) {
            $_select = '';
            $width = self::getControlWidth($width);

            $_select .= '<select name="' . $_name . '" id="'
                . $_id . '"' . $width . '>';
            $_selectd = false;
            if ($aryTopDefaultValue) {
                $_select .= '<option value="' . $aryTopDefaultValue[1] . '"';
                if (($_cvo != '' && $aryTopDefaultValue[1] == $_cvo) ||
                    ($_cvo == '' && !is_null($selectDefaultValue) &&
                        $aryTopDefaultValue[1] == $selectDefaultValue)) {
                    $_select .= ' selected="selected"';
                    $_selectd = true;
                }
                $_select .= '>' . $aryTopDefaultValue[0] . '</option>';
            }
            if (!is_array($aryValue[0]) && strcasecmp('each', $aryValue[0]) == 0) {
                for ($i = $aryValue[1]; $i < $aryValue[2]; $i++) {
                    $_select .= '<option value="' . $i . '"';
                    if (!$_selectd && (($_cvo != '' && $i == $_cvo) ||
                            ($_cvo == '' && !is_null($selectDefaultValue) &&
                                $i == $selectDefaultValue)))
                        $_select .= ' selected="selected"';

                    $_select .= '>' . $i . '</option>';
                }
            } else {
                foreach ($aryValue as $_key => $_value) {
                    $_value = is_array($_value) ? implode(' | ', $_value) : $_value;
                    $_select .= '<option value="' . $_key . '"';
                    if (!$_selectd && (($_cvo != '' && $_key == $_cvo) ||
                            ($_cvo == '' && !is_null($selectDefaultValue) &&
                                $_key == $selectDefaultValue)))
                        $_select .= ' selected="selected"';
                    $_select .= '>' . $_value . '</option>';
                }
            }
            $_select .= '</select> ';
            return $_select;
        } else {
            return $aryValue[intval($_cvo)];
        }
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param null $aryValue
     * @param null $defaultValue
     * @param string $repeatDirection
     * @return string
     */
    public static function radio($literal, $controlName, $controlValueObject = null,
                                 $aryValue = null, $defaultValue = null, $repeatDirection = 'horizontal') {
        $_name = '';
        $_id = '';
        if (is_array($controlName)) {
            $_name = $controlName[0];
            $_id = $controlName[1];
        } else {
            $_name = $controlName;
            $_id = $controlName;
        }
        $_cvo = self::getControlValueObject($controlValueObject, $_name);
        if ($literal) {
            $_radio = '';
            foreach ($aryValue as $_key => $_value) {
                if ($repeatDirection == 'vertical')
                    $_radio .= '<div>';
                $_radio .= '<input type="radio" name="' . $_name .
                    '" id="' . $_id . '_' . $_key
                    . '" value="' . $_key . '"';
                if (($_cvo != '' && $_key == $_cvo) ||
                    ($_cvo == '' && !is_null($defaultValue) &&
                        $_key == $defaultValue))
                    $_radio .= ' checked="checked"';
                $_radio .= ' /><label for="' . $_id . '_' . $_key
                    . '">' . $_value . '</label> ';
                if ($repeatDirection == 'vertical')
                    $_radio .= '</div>';
            }
            return $_radio;
        }
        else {
            return $aryValue[$_cvo];
        }
    }

    /**
     * @param $literal
     * @param $controlName
     * @param null $controlValueObject
     * @param null $aryValue
     * @param null $defaultValue
     * @return null|string
     */
    public static function checkbox($literal, $controlName, $controlValueObject = null,
                                    $aryValue = null, $defaultValue = null) {
        $_name = '';
        $_id = '';
        if (is_array($controlName)) {
            $_name = $controlName[0];
            $_id = $controlName[1];
        } else {
            $_name = $controlName;
            $_id = $controlName;
        }
        $_cvo = self::getControlValueObject($controlValueObject, $_name);
        if ($literal) {
            $_checkbox = '';
            if (is_array($aryValue)) {
                if ($_cvo != '')
                    $aryCvo = explode(',', $_cvo);
                foreach ($aryValue as $_key => $_value) {
                    $_checkbox .= '<input type="checkbox" name="'
                        . $_name . '[]" id="' . $_id . '_'
                        . $_key . '" value="' . $_key . '"';
                    if ($aryCvo != '' && in_array($_key, $aryCvo))
                        $_checkbox .= ' checked="checked"';
                    $_checkbox .= ' /><label for="' . $_id . '_'
                        . $_key . '">' . $_value . '</label> ';
                }
            }
            else {
                $_checkbox = '<input type="checkbox" name="' . $_name
                    . '" id="' . $_id . '" value="1"';
                if (intval($_cvo) == 1)
                    $_checkbox .= ' checked="checked"';
                $_checkbox .= ' /><label for="' . $_id . '">'
                    . $aryValue . '</label> ';
            }
            return $_checkbox;
        }
        else {
            if ($_cvo != 0)
                return $aryValue;
        }
    }

}
