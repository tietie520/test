<?php

namespace App\Repository\Impl;

if (!defined('IN_PX'))
    exit;

use App\Repository\Archives;
use PDO;

class ArchivesImpl implements Archives {

    //仓储层组件
    private function __Repository($value = 'archives') {}

    private function __Inject($db) {}

//    public function count($categoryId, $__Inject = array('$db')) {
    public function count($categoryId) {
        return intval($this->db->table('`#@__@archives`')
            ->where('`category_id` = ? AND `is_display` = 1')
            ->bind(array($categoryId))
            ->count());
    }

    public function find($id) {
        $_field = is_numeric($id) ? 'archives_id' : 'seo_url';
        return $this->db->select('a.*, ars.*, c.`level`, c.`id_tree`, c.`parent_id`,'
            . 'c.`root_id`, c.`is_part`, c.`channel_type`, c.`nav_type`')
            ->table('`#@__@archives` a, `#@__@archives_substance` ars, `#@__@category` c')
            ->where("a.`{$_field}` = ? AND a.`is_display` = 1 AND a.`archives_id` = ars.`archives_id`"
                . ' AND a.`category_id` = c.`category_id` AND c.`is_display` = 1')
            ->bind(array($id))
            ->find();
    }

    public function viewCount($id) {
        $this->db->nonCacheable()->table('`#@__@archives`')->row(array(
            'view_count' => 'view_count + 1'
        ))->where('archives_id = ?')->bind(array(
            $id
        ))->update();
    }

    public function findAll($categoryId, $start = null, $end = null) {

        return $this->db->select('a.`archives_id`, a.`title`, a.`cover`, a.`title_english`, a.`seo_url`, a.`synopsis`'
            . ', a.`cover`, a.`is_status`, a.`release_date`, a.`add_date`, a.`language`, a.`attachment`, asb.`substance`, 
            c.`channel_type`, c2.`list_dir`, a.`video_url`')
            ->table('`#@__@archives` a, `#@__@archives_substance` asb, `#@__@category` c, `#@__@category` c2')
            ->where('a.`category_id` = ? AND a.`is_display` = 1 AND a.`category_id` = c.`category_id`'
                . ' AND c.`root_id` = c2.`category_id` AND asb.`archives_id` = a.`archives_id`')
            ->order('a.`is_status` DESC, a.`release_date`')
            ->limit($start, $end)
            ->bind(array($categoryId))
            ->findAll();
    }

    private function _prevNext($date, $categoryId, $prevOrNext) {
        return $this->db->select('a.`archives_id`, a.`title`, a.`seo_url`, a.`language`, c.`channel_type`, c2.`list_dir`')
            ->table('`#@__@archives` a, `#@__@category` c, `#@__@category` c2')
            ->where("a.`release_date` {$prevOrNext} ? AND a.`category_id` = ? AND a.`is_display` = 1"
                . ' AND a.`category_id` = c.`category_id` AND c.`root_id` = c2.`category_id`')
            ->bind(array($date, $categoryId))
            ->order('a.`release_date`', 'DESC')
            ->find();
    }

    public function prev($date, $categoryId) {
        return $this->_prevNext($date, $categoryId, '<');
    }

    public function next($date, $categoryId) {
        return $this->_prevNext($date, $categoryId, '>');
    }

    /**
     * 可用于循环中[chains()]
     * @return mixed
     */
    public function getAnchorText() {
        return $this->db->chains()->select('`text`, `link_url`')
            ->table('`#@__@anchor_text`')
            ->where('`is_status` = 1')
            ->order('LENGTH(`text`) ASC, `anchor_text_sort`', 'ASC')
            ->findAll();
    }

    public function getFooterLink($categoryId = 0, $isHome = false) {
        if ($isHome) { //首页显示
            $_where = '(`category_id` = ? OR `link_type` = 1) AND `is_status` = 1';
        } else {
            $_where = '`language` = ? AND `is_status` = 1';
        }
//        $this->db->debug();
        return $this->db->select()
            ->table('`#@__@footer_link`')
            ->where($_where)
            ->bind(array($categoryId))
            ->order('footer_link_id', 'ASC')
            ->findAll();
    }

    public function getTagsList($id) {
        return $this->db->mode(PDO::FETCH_COLUMN)->select('t.`tags_text`')
            ->table('`#@__@tags_list` tl, `#@__@tags` t')
            ->where('tl.`archives_id` = ? AND tl.`tags_id` = t.`tags_id`')
            ->bind(array($id))
            ->order('t.tags_id', 'ASC')
            ->findAll();
    }

    public function getTags() {
        return $this->db->select()
            ->table('`#@__@tags`')
            ->order('tags_id', 'ASC')
            ->findAll();
    }

    public function getAd($language, $typeId = 0) {
        return $this->db->select('`ad_title`, `ad_img`, `ad_img_bg`, `ad_url`, `target`')
            ->table('`#@__@ad`')
            ->where('`language` = ? AND `type_id` = ? AND `is_display` = 1')
            ->bind(array($language, $typeId))
            ->order('`ad_sort`', 'ASC')
            ->findAll();
    }

    public function findLogo(){
        return $this->db->select('`synopsis`')
            ->table('`#@__@sys_setting`')
            ->where('`setting_id` = 12')
            ->find();
    }

    public function findHomeArchives($categoryId, $start = null, $end = null, $isHome = false) {
        $_where = ' a.`category_id` = c.`category_id` AND a.`is_display` = 1 AND c.`root_id` = ?';
        $_order = ' a.`sort` DESC, a.`is_status` DESC, a.`release_date` ';
        if ($isHome) {
            $_where =  ' a.`category_id` = c.`category_id` AND a.`is_display` = 1 AND c.`category_id` = ? AND a.`is_status` = 3';
//            $_order = 'a.`sort` DESC, a.`is_status`, a.`release_date`' ;
        }
        return $this->db->select('a.`archives_id`, a.`title_english`, a.`title`, a.`title`, a.`seo_url`, a.`synopsis`'
            . ', a.`cover`, a.`is_status`, a.`release_date` , a.`add_date`, a.`video_url`, a.`language`, c.`channel_type`')
            ->table('`#@__@archives` a, `#@__@category` c')
            ->where($_where)
            ->order($_order)
            ->limit($start, $end)
            ->bind(array($categoryId))
            ->findAll();
    }

    public function findHomeArchivesCount($categoryId) {
        return $this->db->select('a.`archives_id`,a.`title_english`, a.`title`, a.`seo_url`, a.`synopsis`'
            . ', a.`cover`, a.`is_status`, a.`release_date`, a.`language`, c.`channel_type`')
            ->table('`#@__@archives` a, `#@__@category` c')
            ->where(' a.`category_id` = c.`category_id` AND a.`is_display` = 1 AND c.root_id = ?')
            ->order('a.`is_status` DESC, a.`release_date`')
            ->bind(array($categoryId))
            ->count();
    }

}
