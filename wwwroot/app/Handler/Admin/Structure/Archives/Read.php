<?php

namespace App\Handler\Admin\Structure\Archives;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use App\Admin\Helper;
use App\Tools\Auxi;
use Phoenix\Support\MsgHelper;

/**
 * 读取
 */
class Read extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->_pushSetting();

        $_start = ($_POST['page'] - 1) * $_POST['rp'];

        $_where = '0 = 0';
        $_bindParam = array();
        if (isset($_POST['sltLanguage'])) {
            $_where .= ' AND a.`language` = :sltLanguage';
            $_bindParam[':sltLanguage'] = $_POST['sltLanguage'];
        }

        if (isset($_POST['sltDateA']) && $_POST['sltDateA'] && $_POST['sltDateB']) {
            $_where .= ' AND (a.`release_date` BETWEEN :sltDateA AND :sltDateB)';
            $_bindParam[':sltDateA'] = $_POST['sltDateA'];
            $_bindParam[':sltDateB'] = $_POST['sltDateB'];
        }
        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (a.`title` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }
        if (isset($_POST['category_id']) && intval($_POST['category_id']) != 0) {
            $_where .= ' AND a.`category_id` = :category_id';
            $_bindParam[':category_id'] = $_POST['category_id'];
        }

        $_where .= ' AND a.`is_display` = :is_display';
        $_bindParam[':is_display'] = isset($_POST['is_display']) ? intval($_POST['is_display']) : 0;

        $_table = '`#@__@archives` a, `#@__@category` c, `#@__@category` c2';
        $_where .= ' AND a.`category_id` = c.`category_id` AND c.`root_id` = c2.`category_id`';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();

        $_rs = $this->db->select('a.*, c.`category_name`')
            ->table($_table)
            ->where($_where)
            ->order('a.is_status DESC, ' . $_POST['sortName'], $_POST['sortOrder'])
            ->limit($_start, $_POST['rp'])
            ->bind($_bindParam)
            ->findAll();

        $_rsp = array(
            'totalResults' => $_total,
            'rows' => array()
        );
        if ($_total) {
            foreach ($_rs as $m) {
                $_idValue = $m->archives_id;
                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        $m->category_name,
                        $m->title,
                        Helper::createSmallImg($context['__CDN__'], $context['__ASSETS__'], $m->cover, $m->category_name),
                        '<span' . Auxi::getDeepColor($m->is_status) . '>'
                        . $this->setting['aryArchivesStatus'][intval($m->is_status)] . '</span>',
                        $m->sort,
                        Auxi::getTime($m->add_date)
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
