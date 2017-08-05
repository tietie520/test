<?php

namespace App\Admin\System;

if (!defined('IN_PX'))
    exit;

use App\Tools\Auxi;
use App\Admin\AbstractCommon;

/**
 * 统计
 */
class Statistics extends AbstractCommon {

    private function __Controller() {}

    //private function __Route($value = '/system') {}
    protected function __Inject($db, $session) {}

    public function welcome() {
        $_model['framework'] = Auxi::readJsonVersion();
        //die(var_dump($this->session->adminUser['id']));
        return $_model;
    }

    public function statistics() {
        return true;
    }

}
