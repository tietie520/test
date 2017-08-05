<?php

namespace App\Handler\Admin\Setting\User;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;
use App\Tools\Auxi;

/**
 * 添加
 *
 */
class Add extends AbstractCommon {

    public function processRequest(Array & $context) {

        //$this->db->debug();
        if ($this->db->table('`#@__@manager_user`')
            ->where('user_name = ?')
            ->bind(array($_POST['user_name']))
            ->exists()) {
            echo(MsgHelper::json('IS_EXISTS'));
        } else {
            $_POST['role_id'] = intval($_POST['role_id']);
            $_POST['master_id'] = $this->session->adminUser['id'];
            $_POST['add_date'] = time();
            $_POST['release_date'] = time();
            $_POST['language'] = intval($_POST['language']);
            $_POST['guid'] = Auxi::guid();
            $_POST['user_pwd'] = hash_hmac('sha256', $_POST['user_pwd'], $_POST['guid']);

            $_return = $this->db->table('`#@__@manager_user`')
                ->row(array(
                    '`role_id`' => '?',
                    '`user_name`' => '?',
                    '`guid`' => '?',
                    '`user_pwd`' => '?',
                    '`real_name`' => '?',
                    '`email`' => '?',
                    '`master_id`' => '?',
                    '`add_date`' => '?',
                    '`release_date`' => '?',
                    '`language`' => '?'
                ))
                ->bind($_POST)
                ->save();

            echo(MsgHelper::json($_return ? 'SUCCESS' : 'DB_ERROR'));
        }
    }

}
