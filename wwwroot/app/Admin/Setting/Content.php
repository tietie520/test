<?php

namespace App\Admin\Setting;

if (!defined('IN_PX'))
    exit;

use App\Admin\AbstractCommon;

/**
 * 站点配置
 */
class Content extends AbstractCommon {

    private function __Controller() {}

    private function __Route($value = '/setting') {}

    protected function __Inject($db, $session) {}

    public function userContent() {
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select()
                ->table('`#@__@manager_user`')
                ->where('`user_id` = ?')
                ->bind(array($_GET['id']))
                ->find();
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = 0;
        }
        $this->userRole = intval($_GET['id']) == 1 ? '系统默认超级管理员，无法修改角色' :
            $this->_getManagerRoleId($this->rs ? $this->rs->role_id : 0);
        $this->disabled = '';
        if (intval($this->session->adminUser['id']) != 1 && intval($_GET['id']) == 1)
            $this->disabled = ' disabled="disabled"';

        return true;
    }

    public function roleContent() {
        $this->setPower = '\'\'';
        if ($this->_boolCanReadData()) {
            $this->rs = $this->db->select()
                ->table('`#@__@manager_role`')
                ->where('`role_id` = ?')
                ->bind(array($_GET['id']))
                ->find();

            $this->setPower = $this->rs->power_value;
        }
        if (!isset($_GET['id'])) {
            $_GET['id'] = 0;
        }
        return true;
    }

    public function content() {
        $this->rs = $this->db->select()
            ->table('`#@__@sys_setting`')
            ->where('`group_id` = 1')
            ->order('setting_id', 'ASC')
            ->findAll();
        return true;
    }

}
