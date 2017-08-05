<?php

if (!defined('IN_PX'))
    exit;
//返回邮件发送地址缓存
return array(
    'fromMail' => 'no_reply@xxx.com',
    'fromName' => 'xx网',
    'SMTPAuth' => true,
    'charSet' => 'utf-8',
    'encoding' => 'base64',
    'stmps' => array(
//		array('server' => '127.0.0.1', 'port' => 465, 'user' => '**@**.com', 'password' => '**')
    ),
    'errorReceiver' => '*@*.com'
//	'sendErrorSmtp' => array('server' => '', 'port' => 465,
//      'user' => '', 'password' => '') //专门用于发送错误报告的邮件通道
);
