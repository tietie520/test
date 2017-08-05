<?php

namespace App\Repository\Impl;

if (!defined('IN_PX'))
    exit;

use App\Repository\Category;

class CategoryImpl implements Category {

    //仓储层组件
    private function __Repository($value = 'category') {}

    private function __Inject($db) {}

    public function find($categoryId, $languageId) {
        $_field = is_numeric($categoryId) ? 'category_id' : 'list_dir';
        return $this->db->select('c.*, cs.*')
            ->table('`#@__@category` c, `#@__@category_substance` cs')
            ->where("c.`{$_field}` = ? AND c.`category_id` = cs.`category_id` AND c.`language` = ?")
            ->bind(array($categoryId, $languageId))
            ->find();
    }

    public function findAll($where, $bind) {
        return $this->db->option(array(
            'select' => 'c.`category_id`, c.`is_home_display`, c.`category_name`, c.`category_name_english`, c.`landscape`, c.`language`, c.`level`, c.`id_tree`,'
                . 'c.`parent_id`, c.`root_id`, c.`channel_type`, c.`is_part`, c.`home_sort`, c.`list_dir`, c.`target`,'
                . 'c.`nav_type`, cs.`seo_title`, cs.`seo_keywords`, cs.`seo_description`, cs.`substance`, cs.`substance_home`',
            'table' => '`#@__@category` c, `#@__@category_substance` cs',
            'where' => ($where . ' AND c.`level` = ? AND c.`is_display` = 1 AND c.`category_id` = cs.`category_id`'),
            'order' => 'c.`sort` ASC',
            'bind' => $bind
        ))->findAll();
    }
    public function findHomeCategory($categoryId) {
        $_field = is_numeric($categoryId) ? 'category_id' : 'list_dir';
        return $this->db->select('c.*, cs.*')
            ->table('`#@__@category` c, `#@__@category_substance` cs')
            ->where("c.`{$_field}` = ? AND c.`category_id` = cs.`category_id`")
            ->limit(0,1)
            ->bind(array($categoryId))
            ->findAll();
    }
}
