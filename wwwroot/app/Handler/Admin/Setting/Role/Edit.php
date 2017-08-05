<?php

namespace App\Handler\Admin\Setting\Role;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;

/**
 * 修改
 */
class Edit extends AbstractCommon {

    public function processRequest(Array & $context) {
        $_POST['power_value'] = json_encode($_POST['set_power']);
        $_POST['release_date'] = strtotime($_POST['release_date']);
        $_POST['language'] = intval($_POST['language']);
        $_POST['role_id'] = $_POST['id'];

        //$this->db->debug();
        $_return = $this->db->table('`#@__@manager_role`')
            ->row(array(
                '`role_name`' => '?',
                '`synopsis`' => '?',
                '`power_value`' => '?',
                '`release_date`' => '?',
                '`language`' => '?'
            ))
            ->where('`role_id` = ?')
            ->bind($_POST)
            ->update();

        echo(MsgHelper::json($_return ? 'SUCCESS' : ($_return == 0 ? 'NO_CHANGES' : 'DB_ERROR')));
    }

}
