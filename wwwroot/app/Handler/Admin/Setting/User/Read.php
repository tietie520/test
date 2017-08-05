<?php

namespace App\Handler\Admin\Setting\User;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use App\Tools\Auxi;
use Phoenix\Support\MsgHelper;

/**
 * 读取
 */
class Read extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->_pushSetting();

        if (!$_POST['sortName'])
            $_POST['sortName'] = 'mu.user_id';
        if (!$_POST['sortOrder'])
            $_POST['sortOrder'] = 'ASC';

        if (!$_POST['page'])
            $_POST['page'] = 1;
        if (!$_POST['rp'])
            $_POST['rp'] = 10;
        $_sortName = $_POST['sortName'];
        $_sortOrder = $_POST['sortOrder'];
        $_rp = intval($_POST['rp']);
        $_start = (($_POST['page'] - 1) * $_POST['rp']);

        $_where = '0 = 0';
        $_bindParam = array();
        if (isset($_POST['sltLanguage']) && $_POST['sltLanguage'] != '') {
            $_where = ' AND mu.`language` = :sltLanguage';
            $_bindParam[':sltLanguage'] = $_POST['sltLanguage'];
        }

        if (isset($_POST['sltDateA']) && $_POST['sltDateA'] && $_POST['sltDateB']) {
            $_where .= ' AND (mu.`release_date` BETWEEN :sltDateA AND :sltDateB)';
            $_bindParam[':sltDateA'] = $_POST['sltDateA'];
            $_bindParam[':sltDateB'] = $_POST['sltDateB'];
        }
        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (mu.`user_name` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }

        $_table = '`#@__@manager_user` mu, `#@__@manager_role` mr';
        $_where .= ' AND mu.`role_id` = mr.`role_id`';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();
        //$this->db->debug();
        $_rs = $this->db->select('mu.*, mr.`role_name`')
            ->table($_table)
            ->where($_where)
            ->order($_sortName, $_sortOrder)
            ->limit($_start, $_rp)
            ->bind($_bindParam)
            ->findAll();

        $_rsp = array(
            'totalResults' => $_total,
            'rows' => array()
        );
        if ($_total) {
            foreach ($_rs as $m) {
                $_idValue = $m->user_id;
                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        $m->role_name,
                        $m->user_name,
                        $m->real_name,
                        long2ip($m->last_log_ip),
                        $m->log_count,
                        Auxi::getTime($m->last_log_date),
                        Auxi::getTime($m->add_date)
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
