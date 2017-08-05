<?php

namespace App\Service\Impl;

if (!defined('IN_PX'))
    exit;

use App\Service\UCenter;
use Phoenix\Log\Log4p as logger;

/**
 * discuz ucenter,bbs helper
 * 论坛
 */
class UCenterImpl implements UCenter {

    const VERSION = '1.0.0';

    //服务层组件
    private function __Service() {}

    private function __Bundle($value = array('bbs/config/config_ucenter.php', 'bbs/uc_client/client.php')) {}

    /**
     * 注册
     * @param $username
     * @param $password
     * @param $email
     * @param string $questionid
     * @param string $answer
     * @param string $regip
     * @return mixed
     */
    public function ucUserRegister($username, $password, $email, $questionid = '',
                                   $answer = '', $regip = '') {
        return uc_user_register($username, substr($password, 0, 15), $email,
            $questionid, $answer, $regip);
    }

    /**
     * 更新ucenter中用户密码及email。注：只更新ucenter表中，并未通知其它应用
     * !!!!!common_member表并未更新(登录并不验证密码)   * @param $username
     * @param $newpw
     * @param string $email
     * @param string $questionid
     * @param string $answer
     * @return mixed
     */
    public function ucUserEdit($username, $newpw, $email = '', $questionid = '',
                               $answer = '') {
        if ($newpw) {
            $newpw = substr($newpw, 0, 15);
            //logger::debug($newpw);
        }
        return uc_user_edit($username, '', $newpw, $email, 1, $questionid, $answer);
    }

    /**
     * 同步登录
     * @param type $uid
     * @return boolean
     */
    public function curlUcUserSynLogin($uid) {
        preg_match_all('/src="(.[^"]+)"/', uc_user_synlogin($uid), $_out);
        $_output = $this->curlGetUc($_out[1][0]);
        if ($_output !== false && preg_match('/_auth=/i', $_output)) {
            preg_match_all('/Set-Cookie:([^\r\n]*)/i', $_output, $_m);
            //logger::debug($_output);
            foreach ($_m[0] as $_cookie) {
                header($_cookie, false);
            }
            return true;
        }
        return false;
    }

    /**
     * 同步退出
     * @return boolean
     */
    public function curlUcUserSynLogout() {
        preg_match_all('/src="(.[^"]+)"/', uc_user_synlogout(), $_out);
        $_output = $this->curlGetUc($_out[1][0]);
        //logger::debug($_output);
        if ($_output !== false && preg_match('/_auth=/i', $_output)) {
            preg_match('/Set-Cookie:(.*_auth=[^\r\n]*)/i', $_output, $_m);
            //logger::debug($_m);
            header($_m[0], false);
            return true;
        }
        return false;
    }

    /**
     * 同步更新用户名，在ucenter server 以及 ucenter client中都有新增方法
     * @param type $uid
     * @param type $newUserName
     * @return boolean
     */
    public function curlUcUserSynRenameUser($uid, $newUserName) {
        preg_match_all('/src="(.[^"]+)"/', uc_user_synrenameuser($uid, $newUserName), $_out);
        $_output = $this->curlGetUc($_out[1][0], false, true); //只获取body
        if ($_output !== false) {
            if ($_output != '1')
                logger::error('ucenter renameuser error');
            return true;
        }
        return false;
    }
    
    /**
     * curl访问ucenter url 默认只获取header
     * @param $ucphp ucenter接口url
     * @param bool $getHeader 是否将header返回
     * @param bool $getBody true返回 false不返回
     * @return bool|mixed
     */
    public function curlGetUc($ucphp, $getHeader = true, $getBody = false) {
        if (!$ucphp)
            return false;
        $_ch = curl_init();
        curl_setopt($_ch, CURLOPT_URL, $ucphp);
        curl_setopt($_ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($_ch, CURLOPT_HEADER, $getHeader);
        if (!$getBody)
            curl_setopt($_ch, CURLOPT_NOBODY, $getBody);
        $_output = curl_exec($_ch);
        //$_status = curl_getinfo($_ch, CURLINFO_HTTP_CODE);
        curl_close($_ch);
        return $_output;
    }

}
