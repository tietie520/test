<?php

namespace App\Handler\Admin\Structure\FooterLink;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use App\Tools\Auxi;
use Phoenix\Support\MsgHelper;
use App\Admin\Helper;

/**
 * 读取
 */
class Read extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->_pushSetting();

        $_start = (($_POST['page'] - 1) * $_POST['rp']);

        $_where = '0 = 0';
        $_bindParam = array();
        if (isset($_POST['sltLanguage'])) {
            $_where .= ' AND fl.`language` = :sltLanguage';
            $_bindParam[':sltLanguage'] = $_POST['sltLanguage'];
        }

        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (fl.`keywords` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }
        if (isset($_POST['category_id']) && $_POST['category_id'] != '') {
            $_where .= ' AND fl.`category_id` = :category_id';
            $_bindParam[':category_id'] = intval($_POST['category_id']);
        }

        $_table = '`#@__@footer_link` fl LEFT JOIN `#@__@category` c ON fl.`category_id` = c.`category_id`';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();
        //$this->db->debug();
        $_rs = $this->db->select('fl.*, c.`category_name`')
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
                $_idValue = $m->footer_link_id;
                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        intval($m->category_id) == 0 ? '首页链接' : (intval($m->category_id) == 1 ? '英文首页链接' : (intval($m->category_id) == 2 ? '法文首页链接' : $m->category_name)),
                        $m->keywords,
                        $m->link_url,
                        Helper::createSmallImg($context['__CDN__'], $context['__ASSETS__'], $m->link_cover, $m->category_name),
                        $this->setting['aryFooterLinkTarget'][intval($m->target)],
                        '<span' . Auxi::getDeepColor($m->is_status) . '>'
                        . $this->setting['aryAnchorStatus'][intval($m->is_status)] . '</span>',
                        $this->setting['aryFooterLinkType'][intval($m->link_type)]
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
