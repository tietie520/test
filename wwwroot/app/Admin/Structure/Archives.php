<?php

namespace App\Admin\Structure;

if (!defined('IN_PX'))
    exit;

use App\Admin\AbstractCommon;

/**
 * 列表页
 */
class Archives extends AbstractCommon {

    private function __Controller() {}

    private function __Route($value = '/structure') {}

    protected function __Inject($db) {}

    public function archives() {
        $this->sltIDTree = $this->_selectIDTree(array('table' => '`#@__@category`',
            'where' => '`is_display` = 1'),
            array('value' => 'category_id', 'text' => 'category_name'),
            array('name' => 'category_id', 'style' => '180px'),
            array('显示全部' => '0'));
        return true;
    }

    public function footerLink() {
        $this->sltIDTree = $this->_selectIDTree(array('table' => '`#@__@category`',
            'where' => '`is_display` = 1'),
            array('value' => 'category_id', 'text' => 'category_name'),
            array('name' => 'category_id', 'style' => 'width:180px;'),
            array('显示全部' => '', '首页链接' => '0'));
        return true;
    }

}
