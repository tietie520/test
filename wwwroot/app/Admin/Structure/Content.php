<?php

namespace App\Admin\Structure;

if (!defined('IN_PX'))
    exit;

use App\Admin\AbstractCommon;
use App\Model;

/**
 * 内容页
 */
class Content extends AbstractCommon {

    private function __Controller() {}

    //private function __Route($value = '/structure') {}
    protected function __Inject($db) {}

    public function archivesContent() {
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select('a.*, ars.*')
                ->table('`#@__@archives` a, `#@__@archives_substance` ars')
                ->where('a.`archives_id` = ? AND a.`archives_id` = ars.`archives_id`')
                ->bind(array($_GET['id']))
                ->find();
        }
        if (!isset($_GET['parentId'])){
            $_GET['parentId'] = '';
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = '';
        }

        $this->sltIDTree = $this->_selectIDTree(array('table' => '`#@__@category`',
            'where' => '`is_display` = 1'),
            array('value' => 'category_id', 'text' => 'category_name',
                'selected' => $this->rs ? $this->rs->category_id : $_GET['parentId']),
            array('disabled' => $this->pageControl));

        $this->getSort = $this->_getSort('archives');

        $this->banners= $this->db->select('`src`')
            ->table('`#@__@archives_attach`')
            ->where('`archives_id` = ?')
            ->bind(array($_GET['id']))
            ->findAll();
        $this->banners = json_encode($this->banners);

        return true;
    }

    public function categoryContent() {
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select('c.*, cs.*')
                ->table('`#@__@category` c, `#@__@category_substance` cs')
                ->where('c.`category_id` = ? AND c.`category_id` = cs.`category_id`')
                ->bind(array($_GET['id']))
                ->find();
        }
        if (!isset($_GET['parentId'])) {
            $_GET['parentId'] = '';
        }
        if (!isset($_GET['parentChannelType'])) {
            $_GET['parentChannelType'] = 0;
        }
        if (!isset($_GET['parentIsPart'])) {
            $_GET['parentIsPart'] = 0;
        }
        if (!isset($_GET['parentNavType'])) {
            $_GET['parentNavType'] = 0;
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = '';
        }
        $this->sltIDTree = $this->_selectIDTree(array('table' => '`#@__@category`',
            'where' => '`is_display` = 1'),
            array('value' => 'category_id', 'text' => 'category_name',
                'selected' => $this->rs ? $this->rs->parent_id : $_GET['parentId']),
            array('disabled' => $this->pageControl));
        $this->getSort = $this->_getSort();
        $this->getHomeSort = $this->_getSort('category', 'home_sort');
        return true;
    }

    public function adContent() {
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select()
                ->table('`#@__@ad`')
                ->where('`ad_id` = ?')
                ->bind(array($_GET['id']))
                ->find();
        }
        if (!isset($_GET['parentId'])) {
            $_GET['parentId'] = '';
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = '';
        }
        $this->getSort = $this->_getSort('ad', 'ad_sort');
        return true;
    }

    public function anchorTextContent() {
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select()
                ->table('`#@__@anchor_text`')
                ->where('`anchor_text_id` = ?')
                ->bind(array($_GET['id']))
                ->find();
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = '';
        }
        return true;
    }

    public function footerLinkContent() {
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select()
                ->table('`#@__@footer_link`')
                ->where('`footer_link_id` = ?')
                ->bind(array($_GET['id']))
                ->find();
        }
        if (!isset($_GET['parentId'])) {
            $_GET['parentId'] = '';
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = '';
        }
        $this->sltIDTree = $this->_selectIDTree(array('table' => '`#@__@category`',
            'where' => '`is_display` = 1'),
            array('value' => 'category_id', 'text' => 'category_name',
                'selected' => $this->rs ? $this->rs->category_id : $_GET['parentId']),
            array('disabled' => $this->pageControl),
            array('首页链接' => '0', '英文首页链接' => '1', '法文首页链接' => '2'));

        return true;
    }

}
