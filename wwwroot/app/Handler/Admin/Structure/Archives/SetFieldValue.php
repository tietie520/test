<?php

namespace App\Handler\Admin\Structure\Archives;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;

/**
 * 修改
 */
class SetFieldValue extends AbstractCommon {

    public function processRequest(Array & $context) {
        echo($this->_setFieldStatus(explode(',', $_POST['id']),
            '`#@__@archives`', $_POST['field'], 'archives_id',
            false, $_POST['fieldValue']));
    }

}
