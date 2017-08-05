<?php

namespace App\Handler\Admin\Structure\Ad;

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

        $_POST['type_id'] = intval($_POST['type_id']);
        $_POST['ad_title'] = trim($_POST['ad_title']);
        $_POST['is_display'] = intval($_POST['is_display']);
        $_POST['master_id'] = $this->session->adminUser['id'];
        $_POST['ad_sort'] = intval($_POST['ad_sort']);
        $_POST['target'] = intval($_POST['target']);
        $_POST['ad_id'] = $_POST['id'];

        $_return = $this->db->table('`#@__@ad`')
            ->row(array(
                '`type_id`' => '?',
                '`ad_title`' => '?',
                '`ad_img`' => '?',
                '`ad_img_bg`' => '?',
                '`ad_url`' => '?',
                '`is_display`' => '?',
                '`master_id`' => '?',
                '`start_date`' => '?',
                '`end_date`' => '?',
                '`ad_sort`' => '?',
                '`target`' => '?',
                '`language`' => '?'
            ))
            ->where('`ad_id` = ?')
            ->bind($_POST)
            ->update();

        $this->_createImg('ad_img', false); //不生成水印图
        $this->_createImg('ad_img_bg', false); //不生成水印图
        $this->cache->delete('aryAdRotator' . $_POST['type_id']);

        echo(MsgHelper::json($_return ? 'SUCCESS' : ($_return == 0 ? 'NO_CHANGES' : 'DB_ERROR')));
    }

}
