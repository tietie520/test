<?php

namespace App\Handler\Admin\Structure\Ad;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;

/**
 * 删除
 */
class Delete extends AbstractCommon {

    public function processRequest(Array & $context) {
        $_ary = explode(',', $_POST['id']);
        foreach ($_ary as $_id) {
            $this->cache->delete('aryAdRotator' . $this->db->field('type_id')
                    ->table('`#@__@ad`')
                    ->where('ad_id = ?')
                    ->bind(array($_id))
                    ->find());
        }
        echo($this->_publicDeleteFieldByPostItem($_POST['id'], '`#@__@ad`', 'ad_id', 'ad_img'));
    }

}
