<?php

namespace App\Service\Impl;

if (!defined('IN_PX'))
    exit;

use App\Service\Mail;
use PHPMailer\PHPMailer;
use Phoenix\Log\Log4p as logger;

class MailImpl implements Mail {

    private function __Service() {}

    private function __Value($cfg) {}

    private function __Bundle($smtpServer = 'data/mail.cache.php') {}

    private function __Inject(PHPMailer $mailer) {}

    private $_content = '';
    private $_title = '';
    private $_att = null;
    private $_wrap2SystemMail = false;

    /**
     *
     * @param type|string $postMails 字符串或者数组
     * @param type|string $title
     * @param type|string $content
     * @param type $smtpServer 指定的smtp服务器，一般用作发送错误报告(私有的可靠稳定的邮件服务器)
     * @param bool|type $debug
     * @return bool
     */
    public function send($postMails = '邮件地址', $title = '标题', $content = '内容',
                         $smtpServer = null, $debug = false) {
        if (!is_null($smtpServer)) {
            $_tmpSmtpServer = $smtpServer;
        } else if (count($this->smtpServer['stmps']) == 0) {
            return false;
        } else {
            $_tmpSmtpServer = $this->smtpServer['stmps'][array_rand($this->smtpServer['stmps'])];
        }
        if (!isset($_tmpSmtpServer['server'])) {
            return false;
        }
        $this->_title = $title;
        $this->_content = preg_replace('/\[\\\]/i', '', $content);
        $this->_wrap2SystemMail();
        $this->mailer->CharSet = $this->smtpServer['charSet'];
        $this->mailer->Encoding = $this->smtpServer['encoding'];
        $this->mailer->IsSMTP(); // telling the class to use SMTP
        // enables SMTP debug information (for testing)
        // 1 = errors and messages// 2 = messages only
        if ($debug) {
            $this->mailer->SMTPDebug = 2;
        }
        $this->mailer->SMTPAuth = $this->smtpServer['SMTPAuth'];   // enable SMTP authentication
        $this->mailer->SMTPSecure = $_tmpSmtpServer['port'] == 465 ? 'ssl' : '';  // sets the prefix to the servier
        $this->mailer->Host = $_tmpSmtpServer['server'];   // sets GMAIL as the SMTP server
        $this->mailer->Port = $_tmpSmtpServer['port']; // set the SMTP port for the GMAIL server
        $this->mailer->Username = $_tmpSmtpServer['user'];  // GMAIL username
        $this->mailer->Password = $_tmpSmtpServer['password'];   // GMAIL password

        $this->mailer->SetFrom($this->smtpServer['fromMail'], $this->smtpServer['fromName']);
        //$mail->AddReplyTo('support@xxx.com', 'xx网');
        $this->mailer->AltBody = '请使用支持html格式的邮件接收器查看'; // optional, comment out and test
        $this->mailer->Subject = strip_tags($title);
        $this->mailer->MsgHTML($this->_content);
        $this->mailer->ClearAddresses();
        if (is_array($postMails)) {
            foreach ($postMails as $_v)
                $this->mailer->AddAddress($_v);
        } else {
            $this->mailer->AddAddress($postMails);
        }

        $this->mailer->ClearAttachments();
        if (!is_null($this->_att)) {
            if (is_array($this->_att)) {
                foreach ($this->_att as $_v) {
                    $this->mailer->AddAttachment($_v);
                }
            } else {
                $this->mailer->AddAttachment($this->_att);
            }
        }

        if (!$this->mailer->Send()) {
            $_err = print_r($postMails, true) . '--' . $_tmpSmtpServer['server']
                . '====>' . $this->mailer->ErrorInfo;
            logger::error($_err);
            if ($this->smtpServer['errorReceiver']) {
                $this->send($this->smtpServer['errorReceiver'], '邮件smtp错误报告',
                    $_err, $this->smtpServer['sendErrorSmtp']); //提交错误报告
            }
            return false;
        }
        return true;
    }

    /**
     * 添加附件，附件必须为绝对地址
     * @param type $att
     * @return \Service_Mail
     */
    public function attachment($att = null) {
        $this->_att = null;
        if ($att) {
            $this->_att = $att;
        }
        return $this;
    }

    /**
     * 包裹为系统邮件
     * @return \Service_Mail
     */
    public function wrap2SystemMail() {
        $this->_wrap2SystemMail = true;
        return $this;
    }

    /**
     * _包裹为系统邮件
     */
    public function _wrap2SystemMail() {
        if ($this->_wrap2SystemMail) {
            $this->_content = '<h2 align="center"><font color="#870c0f" size=6>' . $this->_title . '</font></h2>
			<hr>
			' . $this->_content . '
			<hr>
			<p align="center"><font color="#50681a" size="2">此邮件由系统发出，请勿回复此邮件。</font></p>';
        }
    }

    /**
     * 增加退订信息
     * @param type|string $url
     * @return \Service_Mail
     */
    public function unsubscribe($url = '/unsubscribe/*') {
        $this->_content .= '
			<hr>
			<p>如果您不愿意继续订阅此类邮件，请<a href="' . $this->cfg['base_host']
            . $url . '" target="_blank">点此退订</a>此类邮件。</font></p>';
        return $this;
    }

}
