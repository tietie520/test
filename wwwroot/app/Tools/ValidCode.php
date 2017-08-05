<?php

namespace App\Tools;

if (!defined('IN_PX'))
    exit;

//验证码类
class ValidCode {

    private static $_setKey = 'validCode';
    private static $_charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; //随机因子
    private static $_code; //验证码
    private static $_codelen = 5;  //验证码长度
    private static $_width = 120;  //宽度
    private static $_height = 32;  //高度
    private static $_img;  //图形资源句柄
    private static $_font;  //指定的字体
    private static $_fontsize = 20; //指定字体大小
    private static $_fontcolor;   //指定字体颜色

    //生成随机码
    private static function _createCode() {
        $_len = strlen(self::$_charset) - 1;
        for ($i = 0; $i < self::$_codelen; $i++) {
            self::$_code .= self::$_charset[mt_rand(0, $_len)];
        }
    }

    //生成背景
    private static function _createBg() {
        self::$_img = imagecreatetruecolor(self::$_width, self::$_height);
        $color = imagecolorallocate(self::$_img,
            mt_rand(157, 255),
            mt_rand(157, 255),
            mt_rand(157, 255));
        imagefilledrectangle(self::$_img,
            0, self::$_height, self::$_width, 0, $color);
    }

    //生成文字
    private static function _createFont() {
        self::$_font = dirname(dirname(__DIR__)) . '/data/font/t'
            . mt_rand(0, 6) . '.ttf';
        //self::$_font = dirname(dirname(__DIR__)) . '/data/font/t0.ttf';

        $_x = self::$_width / self::$_codelen;
        for ($i = 0; $i < self::$_codelen; $i++) {
            self::$_fontcolor = imagecolorallocate(self::$_img,
                mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imagettftext(self::$_img,
                self::$_fontsize,
                mt_rand(-30, 30),
                $_x * $i + mt_rand(1, 5),
                self::$_height / 1.4,
                self::$_fontcolor,
                self::$_font,
                self::$_code[$i]);
        }
    }

    //生成线条、雪花
    private static function _createLine() {
        for ($i = 0; $i < 6; $i++) {
            $color = imagecolorallocate(self::$_img,
                mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline(self::$_img,
                mt_rand(0, self::$_width),
                mt_rand(0, self::$_height),
                mt_rand(0, self::$_width),
                mt_rand(0, self::$_height), $color);
        }
        for ($i = 0; $i < 100; $i++) {
            $color = imagecolorallocate(self::$_img,
                mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring(self::$_img,
                mt_rand(1, 5),
                mt_rand(0, self::$_width),
                mt_rand(0, self::$_height),
                '*', $color);
        }
    }

    //保存验证码
    private static function _saveSessionCode() {
        isset($_SESSION) || session_start();
        $_SESSION[self::$_setKey] = self::$_code;
    }

    //输出
    private static function _outPut() {
        header('Pragma: no-cache');
        header('Content-type:image/png');
        imagepng(self::$_img);
        imagedestroy(self::$_img);
    }

    //改变session验证值
    public static function chgSessionCode() {
        self::_createCode();
        self::_saveSessionCode();
        //var_dump($_SESSION[self::$_setKey]);
    }

    //对外生成
    public static function entry() {
        self::chgSessionCode();

        self::_createBg();
        self::_createLine();
        self::_createFont();
        self::_outPut();
    }

    //输出session验证值到图片
    public static function getSessionImg() {
        session_start();
        if (isset($_SESSION[self::$_setKey])) {
            self::$_code = $_SESSION[self::$_setKey];
        } else {
            self::chgSessionCode();
        }
        self::_createBg();
        self::_createLine();
        self::_createFont();
        self::_outPut();
    }

}
