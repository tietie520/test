<?php

namespace App\Handler\Admin\Setting\User;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;
use App\Tools\Auxi;

/**
 * 修改
 */
class Edit extends AbstractCommon {

    public function processRequest(Array & $context) {
        //$this->db->debug();
        if (intval($_POST['id']) == 1)
            $_POST['role_id'] = 1; //超级管理员角色
        $_POST['role_id'] = intval($_POST['role_id']);
        $_POST['release_date'] = strtotime($_POST['release_date']);
        $_POST['language'] = intval($_POST['language']);
        $_POST['user_id'] = $_POST['id'];

        $_return = $this->db->table('`#@__@manager_user`')
            ->row(array(
                '`role_id`' => '?',
                '`user_name`' => '?',
                '`real_name`' => '?',
                '`email`' => '?',
                '`release_date`' => '?',
                '`language`' => '?'
            ))
            ->where('`user_id` = ?')
            ->bind($_POST)
            ->update();

        if (!empty($_POST['user_pwd'])) {
            $_POST['guid'] = Auxi::guid();
            $_POST['user_pwd'] = hash_hmac('sha256', $_POST['user_pwd'], $_POST['guid']);
            $_return += $this->db->table('`#@__@manager_user`')
                ->row(array(
                    '`user_pwd`' => '?',
                    '`guid`' => '?'
                ))
                ->where('`user_id` = ?')
                ->bind(array(
                    $_POST['user_pwd'],
                    $_POST['guid'],
                    $_POST['user_id']
                ))
                ->update();
        }

        echo(MsgHelper::json($_return ? 'SUCCESS' : 'DB_ERROR'));
    }

}
