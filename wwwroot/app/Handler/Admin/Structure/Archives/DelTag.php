<?php

namespace App\Handler\Admin\Structure\Archives;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;

/**
 * 删除关键字
 */
class DelTag extends AbstractCommon {

    public function processRequest(Array & $context) {
        $_r = $this->db->table('`#@__@tags`')
            ->where('`tags_id` = ?')
            ->bind(array($_POST['tagId']))
            ->delete();
        if ($_r) {
            $this->db->table('`#@__@tags_list`')
                ->where('`tags_id` = ?')
                ->bind(array($_POST['tagId']))
                ->delete();
        }
        echo(MsgHelper::json($_r ? 'SUCCESS' : 'DB_ERROR'));
    }

}
