<?php

namespace App\Handler\Admin\Structure\Ad;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;

/**
 * 修改
 */
class SetDisplay extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->_pushSetting();
        $_ary = explode(',', $_POST['id']);
        foreach ($_ary as $_id) {
            $this->cache->delete('aryAdRotator' . $this->db->field('type_id')
                    ->table('`#@__@ad`')
                    ->where('ad_id = ?')
                    ->bind(array($_id))
                    ->find());
        }
        echo($this->_setFieldStatus($_ary, '`#@__@ad`', 'is_display', 'ad_id'));
    }

}
