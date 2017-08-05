<?php

namespace App\Handler\Admin\Structure\FooterLink;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;

/**
 * 修改
 */
class SetDisplay extends AbstractCommon {

    public function processRequest(Array & $context) {
        echo($this->_setFieldStatus(explode(',', $_POST['id']),
            '`#@__@footer_link`', 'is_status', 'footer_link_id'));
    }

}
