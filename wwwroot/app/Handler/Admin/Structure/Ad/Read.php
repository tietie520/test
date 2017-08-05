<?php

namespace App\Handler\Admin\Structure\Ad;

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
            $_POST['sortName'] = 'a.ad_id';
        if (!$_POST['sortOrder'])
            $_POST['sortOrder'] = 'ASC';

        if (!$_POST['page'])
            $_POST['page'] = 1;
        if (!$_POST['rp'])
            $_POST['rp'] = 10;
        $_start = (($_POST['page'] - 1) * $_POST['rp']);

        $_where = '0 = 0';
        $_bindParam = array();
        if (isset($_POST['sltLanguage'])) {
            $_where .= ' AND a.`language` = :sltLanguage';
            $_bindParam[':sltLanguage'] = $_POST['sltLanguage'];
        }

        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (a.`ad_title` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }
        if (isset($_POST['type_id']) && $_POST['type_id'] != '') {
            $_where .= ' AND a.`type_id` = :type_id';
            $_bindParam[':type_id'] = $_POST['type_id'];
        }

        $_table = '`#@__@ad` a';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();
        //$this->db->debug();
        $_rs = $this->db->select('a.*')
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
                $_idValue = $m->ad_id;
                $_tempBool = $m->end_date > time() ? 0 : 1; //判断是否已到期 0未到期 1 到期
                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        $this->setting['aryAd'][intval($m->type_id)],
                        $m->ad_title,
                        Helper::createSmallImg($context['__CDN__'], $context['__ASSETS__'], $m->ad_img, $m->ad_title),
                        Helper::createSmallImg($context['__CDN__'], $context['__ASSETS__'], $m->ad_img_bg, $m->ad_title),
                        $m->ad_url,
                        '<span' . Auxi::getDeepColor(intval($m->is_display)) . '>'
                        . $this->setting['aryBool'][intval($m->is_display)] . '</span>',
                        $m->ad_sort
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
