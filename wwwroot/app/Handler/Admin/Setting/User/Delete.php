<?php

namespace App\Handler\Admin\Setting\User;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use App\Tools\Auxi;
use Phoenix\Support\MsgHelper;

/**
 * 删除
 */
class Delete extends AbstractCommon {

    public function processRequest(Array & $context) {
        $_id = Auxi::databaseNeedId($_POST['id'], 1);
        if ($_id == 1) {
            echo(MsgHelper::json('NEED'));
        } else {
            echo($this->_publicDeleteFieldByPostItem($_id, '`#@__@manager_user`', 'user_id'));
        }
    }

}
