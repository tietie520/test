<?php

namespace App\Admin\System;

if (!defined('IN_PX'))
    exit;

use App\Admin\AbstractCommon;

/**
 * 登录
 * 会员不基于session，基于数据库的内存表或者内存缓存
 */
class Login extends AbstractCommon {

    private function __Controller() {}

    //private function __Route($value = '/system') {}
    protected function __Inject($session) {}

    public function login() {
        if (!is_null($this->session->adminUser['id'])) {
            $_url = isset($_GET['url']) ?
                urldecode($_GET['url']) :
                "http://{$_SERVER['HTTP_HOST']}" . $this->__ROOT__ . $this->__VC__ . '/system/welcome';
            header('Location: ' . $_url);
            exit;
        }
        return true;
    }

}
