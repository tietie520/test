<?php

namespace App\Handler\Admin\Structure\Category;

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

        if (!$_POST['sortName'])
            $_POST['sortName'] = 'c.id_tree';
        if (!$_POST['sortOrder'])
            $_POST['sortOrder'] = 'ASC';

        if (!$_POST['page'])
            $_POST['page'] = 1;
        if (!$_POST['rp'])
            $_POST['rp'] = 10;
        $_start = ($_POST['page'] - 1) * $_POST['rp'];

        $_where = '0 = 0';
        $_bindParam = array();
        if (isset($_POST['sltLanguage'])) {
            $_where .= ' AND c.`language` = :sltLanguage';
            $_bindParam[':sltLanguage'] = $_POST['sltLanguage'];
        }

        if (isset($_POST['sltDateA']) && $_POST['sltDateA'] && $_POST['sltDateB']) {
            $_where .= ' AND (c.`release_date` BETWEEN :sltDateA AND :sltDateB)';
            $_bindParam[':sltDateA'] = $_POST['sltDateA'];
            $_bindParam[':sltDateB'] = $_POST['sltDateB'];
        }
        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (c.`category_name` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }

        //$this->db->debug();
        $_table = '`#@__@category` c, `#@__@manager_user` mu';
        $_where .= ' AND c.`master_id` = mu.`user_id`';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();
        $_rs = $this->db->select('c.*, mu.`user_name`')
            ->table($_table)
            ->where($_where)
            ->order($_POST['sortName'], $_POST['sortOrder'])
            ->limit($_start, $_POST['rp'])
            ->bind($_bindParam)
            ->findAll();

        $_rsp = array(
            'totalResults' => $_total,
            'rows' => array()
        );
        if ($_total) {
            foreach ($_rs as $m) {
                $_idValue = $m->category_id;
                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        '<span' . Auxi::getDeepColor($m->level) . '>' . $m->level . '</span>',
                        Helper::getTreeIMG($m->level, $m->category_name, $context['__ASSETS__']),
                        $m->category_name_english,
                        '<span' . Auxi::getDeepColor($m->is_part) . '>' . $this->setting['aryPartShow'][intval($m->is_part)] . '</span>',
                        Helper::createSmallImg($context['__CDN__'], $context['__ASSETS__'], $m->landscape, $m->category_name),
                        '<span' . Auxi::getDeepColor($m->nav_type) . '>' . $this->setting['aryNavType'][intval($m->nav_type)] . '</span>',
                        '<span' . Auxi::getDeepColor($m->is_display) . '>' . $this->setting['aryBool'][intval($m->is_display)] . '</span>',
                        '<span' . Auxi::getDeepColor($m->is_home_display) . '>' . $this->setting['aryBool'][intval($m->is_home_display)] . '</span>',
                        $m->home_sort,
                        $m->user_name,
                        Auxi::getTime($m->add_date),
                        Auxi::getTime($m->release_date)
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
