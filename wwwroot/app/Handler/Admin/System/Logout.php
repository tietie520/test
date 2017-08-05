<?php

namespace App\Handler\Admin\System;

if (!defined('IN_PX'))
    exit;

use Phoenix\Routing\IHttpHandler;

/**
 * 会员退出
 */
class Logout implements IHttpHandler {

    private function __Handler() {}

    //private function __Value($cfg) {}
    private function __Inject($session) {}

    public function processRequest(Array & $context) {
        $this->session->destory('adminUser');
        header("Location: http://{$_SERVER['HTTP_HOST']}{$context['__ROOT__']}{$context['__PACKAGE__']}/system/login");
        exit;
    }

}
