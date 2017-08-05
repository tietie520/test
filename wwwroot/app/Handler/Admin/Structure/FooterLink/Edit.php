<?php

namespace App\Handler\Admin\Structure\FooterLink;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;

/**
 * 修改
 */
class Edit extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->_pushSetting();
        $this->_processingParameters();

        $_POST['category_id'] = intval($_POST['category_id']);
        $_POST['keywords'] = trim($_POST['keywords']);
        $_POST['target'] = intval($_POST['target']);
        $_POST['is_status'] = intval($_POST['is_status']);
        $_POST['link_type'] = intval($_POST['link_type']);
        $_POST['footer_link_id'] = $_POST['id'];

        //$this->db->debug();
        $_return = $this->db->table('`#@__@footer_link`')
            ->row(array(
                '`category_id`' => '?',
                '`keywords`' => '?',
                '`target`' => '?',
                '`link_url`' => '?',
                '`is_status`' => '?',
                '`link_type`' => '?',
                '`link_cover`' => '?',
                '`language`' => '?'
            ))
            ->where('`footer_link_id` = ?')
            ->bind($_POST)
            ->update();

        if ($_POST['link_cover'] != '') {
            $this->_createImg('link_cover');
        }

        echo(MsgHelper::json($_return ? 'SUCCESS' : ($_return == 0 ? 'NO_CHANGES' : 'DB_ERROR')));
    }

}
