<?php

namespace App\Admin\Interceptor;

if (!defined('IN_PX'))
    exit;

use Phoenix\Interceptor\AbstractAdapter;
use Phoenix\Log\Log4p as logger;

/**
 * 后台登录拦截器
 */
class Login extends AbstractAdapter {

    private function __Bundle($adminMap = 'data/adminMap.cache.php') {}

    private function __Inject($db, $session) {}

    public function preHandle(Array & $context) {
        if (!is_null($this->session->adminUser)) {//后台用户
            $context['adminMap'] = & $this->adminMap;

            $context['adminPower'] = json_decode($this->db->field('mr.`power_value`')
                ->table('`#@__@manager_user` mu, `#@__@manager_role` mr')
                ->where('mu.`user_id` = ? AND mu.`role_id` = mr.`role_id`')
                ->bind(array($this->session->adminUser['id']))
                ->find());
            return true;
        } else {
            //因为后台基于js调用环境，故打破封装性
            die('<script>(function(){window.top.location.href=\''
                . $context['__ROOT__'] . $context['__VC__']
                . '/system/login?url=\' + encodeURIComponent(window.top.location.href);})()</script>');
            return false;
        }
    }

}
