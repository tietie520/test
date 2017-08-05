<?php

namespace App\Handler\Admin\Structure\Category;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;
use Exception;

/**
 * 删除
 */
class Delete extends AbstractCommon {

    public function processRequest(Array & $context) {
        try {
            $this->db->beginTransaction();

            $this->cache->delete(array('aryCategoryDataView', 'footerCategoryNavigation'));
            echo($this->_publicDeleteFieldByPostItem($_POST['id'],
                array('`#@__@category`', '`#@__@category_substance`'),
                'category_id', 'landscape'));

            $this->db->commit();
        } catch (Exception $e) {

            $this->db->rollBack();
            echo(MsgHelper::json('DB_ERROR'));
        }
    }

}
