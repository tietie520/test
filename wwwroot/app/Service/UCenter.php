<?php
namespace App\Service;

/**
 * discuz ucenter,bbs helper
 * 论坛
 */
interface UCenter {
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
    public function ucUserRegister($username, $password, $email, $questionid = '', $answer = '', $regip = '');

    /**
     * 更新ucenter中用户密码及email。注：只更新ucenter表中，并未通知其它应用
     * !!!!!common_member表并未更新(登录并不验证密码)   * @param $username
     * @param $newpw
     * @param string $email
     * @param string $questionid
     * @param string $answer
     * @return mixed
     */
    public function ucUserEdit($username, $newpw, $email = '', $questionid = '', $answer = '');

    /**
     * 同步登录
     * @param type $uid
     * @return boolean
     */
    public function curlUcUserSynLogin($uid);

    /**
     * 同步退出
     * @return boolean
     */
    public function curlUcUserSynLogout();

    /**
     * 同步更新用户名，在ucenter server 以及 ucenter client中都有新增方法
     * @param type $uid
     * @param type $newUserName
     * @return boolean
     */
    public function curlUcUserSynRenameUser($uid, $newUserName);

    /**
     * curl访问ucenter url 默认只获取header
     * @param $ucphp ucenter接口url
     * @param bool $getHeader 是否将header返回
     * @param bool $getBody true返回 false不返回
     * @return bool|mixed
     */
    public function curlGetUc($ucphp, $getHeader = true, $getBody = false);
}