<?php

namespace App\Handler\Admin\Structure\Category;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;

/**
 * 更改
 */
class SetFieldStatus extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->cache->delete(array('aryCategoryDataView', 'footerCategoryNavigation'));
        echo($this->_setFieldStatus(explode(',', $_POST['id']),
            '`#@__@category`', $_POST['field'], 'category_id'));
    }

}
