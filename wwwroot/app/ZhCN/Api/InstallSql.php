<?php

namespace App\ZhCN\Api;

if (!defined('IN_PX'))
    exit;

use App\Repository;

class InstallSql {

    private function __RestController() {}

    private function __Route($value = '/api') {}

    protected function __Inject($db) {}

    public function install() {
        $_sqlLock = ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'dbInstall.lock';
        if (is_file($_sqlLock)) {
            return 404;
        } else {
            header('Content-type: text/html; charset=utf-8');
            $_sqlUrl = ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'dbInstall.sql';
            if (file_exists($_sqlUrl)) {
                $this->db->query(file_get_contents($_sqlUrl));
                //删除文件
                @unlink($_sqlUrl);
                file_put_contents($_sqlLock, '');
                echo '数据库初始化成功!';
            } else {
                echo '数据库文件不存在!';
            }
        }
        exit;
    }

}
