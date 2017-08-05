<?php

namespace App\Handler\Admin\System;

if (!defined('IN_PX'))
    exit;

use App\Tools\Auxi;
use Phoenix\Routing\IHttpHandler;
use Phoenix\Support\MsgHelper;
use App\Tools\ValidCode;

/**
 * 会员登录
 */
class Login implements IHttpHandler {

    private function __Handler($value = 'login') {}

    //private function __Interceptor($value = 'login') {}
    //private function __Value($cfg) {}
    private function __Inject($db, $session) {}

    public function processRequest(Array & $context) {
        //$this->Test($this->db);
        session_start();
        if (empty($_POST['userName']))
            die(MsgHelper::json('USER_EMPTY'));
        if (empty($_POST['userPwd']))
            die(MsgHelper::json('PASS_EMPTY'));
//        if (empty($_POST['validCode']))
//            die(MsgHelper::json('VALIDCODE_EMPTY'));
//        if (empty($_SESSION['validCode']))
//            die(MsgHelper::json('VALIDCODE_LOSE'));
//        if (strcasecmp($_SESSION['validCode'], $_POST['validCode']) != 0)
//            die(MsgHelper::json('VALIDCODE_ERROR'));

//        $this->db->debug();
        $_rs = $this->db->select('a.*, b.`power_value`')
            ->table('`#@__@manager_user` a, `#@__@manager_role` b')
            ->where('a.`user_name` = ? AND a.`role_id` = b.`role_id`')
            ->bind(array($_POST['userName']))->find();

        if ($_rs) {
            if ($_rs->is_err_lock > 0 && time() - $_rs->err_lock_time < 3600) {
                echo(MsgHelper::json('IS_LOCK', '1小时'));
            } else if (strcmp($_rs->user_pwd, hash_hmac('sha256', $_POST['userPwd'], $_rs->guid)) != 0) {//password error
                $_tryCount = 5;
                if ($_rs->pwd_err_count + 1 >= $_tryCount) {
                    $this->db->table('`#@__@manager_user`')
                        ->row(array(
                            '`pwd_err_count`' => '`pwd_err_count` + 1',
                            '`is_err_lock`' => '1',
                            '`err_lock_time`' => '?'
                        ))
                        ->where('`user_id` = ?')
                        ->bind(array(
                            time(), $_rs->user_id
                        ))
                        ->update();
                    echo(MsgHelper::json('IS_LOCK', '1小时'));
                } else {
                    $this->db->table('`#@__@manager_user`')
                        ->row(array(
                            '`pwd_err_count`' => '`pwd_err_count` + 1'
                        ))
                        ->where('`user_id` = ?')
                        ->bind(array(
                            $_rs->user_id
                        ))
                        ->update();
                    echo(MsgHelper::json('PASSWORD_ERROR', null, $_tryCount - $_rs->pwd_err_count - 1));
                }
            } else {
                //$this->db->debug();
                $this->db->table('`#@__@manager_user`')
                    ->row(array(
                        '`last_log_date`' => '?',
                        '`log_count`' => '`log_count` + 1',
                        '`last_log_ip`' => '?',
                        '`pwd_err_count`' => '0',
                        '`is_err_lock`' => '0'
                    ))
                    ->where('`user_id` = ?')
                    ->bind(array(
                        time(),
                        ip2long(Auxi::getIP()),
                        $_rs->user_id
                    ))
                    ->update();
                $this->session->adminUser = array(
                    'id' => $_rs->user_id,
                    'name' => $_rs->user_name,
                    'lastLogDate' => Auxi::getTime($_rs->last_log_date),
                    'lastLogIP' => long2ip($_rs->last_log_ip)
                );
                $_redirect = $context['__ROOT__'] .  $context['__PACKAGE__'] . '/system/welcome';
                if (strpos($_POST['url'], 'http') !== false) {
                    $_redirect = urldecode($_POST['url']);
                }
                echo(MsgHelper::json('SUCCESS', $_redirect));
            }
        } else {
//            ValidCode::chgSessionCode();
            echo(MsgHelper::json('NOT_EXISTS'));
        }
    }

}
