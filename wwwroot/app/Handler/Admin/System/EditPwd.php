<?php

namespace App\Handler\Admin\System;

if (!defined('IN_PX'))
    exit;

use Phoenix\Routing\IHttpHandler;
use Phoenix\Support\MsgHelper;

/**
 * 会员退出
 */
class EditPwd implements IHttpHandler {

    private function __Handler() {}

    private function __Inject($db, $session) {}

    public function processRequest(Array & $context) {
        if (isset($_POST['user_pwd']) && !empty($_POST['user_pwd'])) {
            $_r = $this->db->table('`#@__@manager_user`')
                ->row(array(
                    '`user_pwd`' => '?'
                ))
                ->where('`user_id` = ?')
                ->bind(array(
                    md5($_POST['user_pwd']),
                    $this->session->adminUser['id']
                ))
                ->update();
            echo($_r ? MsgHelper::json('SUCCESS', '密码修改成功') :
                ($_r == 0 ? MsgHelper::json('NO_CHANGES') : MsgHelper::json('DB_ERROR')));
        }
    }

}
