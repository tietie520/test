<?php
namespace App\Service;

use App\Service\Impl\type;

interface Mail {
    /**
     *
     * @param type|string $postMails 字符串或者数组
     * @param type|string $title
     * @param type|string $content
     * @param type $smtpServer 指定的smtp服务器，一般用作发送错误报告(私有的可靠稳定的邮件服务器)
     * @param bool|type $debug
     * @return bool
     */
    public function send($postMails = '邮件地址', $title = '标题', $content = '内容', $smtpServer = null, $debug = false);

    /**
     * 添加附件，附件必须为绝对地址
     * @param type $att
     * @return \Service_Mail
     */
    public function attachment($att = null);

    /**
     * 包裹为系统邮件
     * @return \Service_Mail
     */
    public function wrap2SystemMail();

    /**
     * _包裹为系统邮件
     */
    public function _wrap2SystemMail();

    /**
     * 增加退订信息
     * @param type|string $url
     * @return \Service_Mail
     */
    public function unsubscribe($url = '/unsubscribe/*');
}