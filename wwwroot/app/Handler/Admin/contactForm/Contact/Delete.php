<?php

namespace App\Handler\Admin\ContactForm\Contact;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use Phoenix\Support\MsgHelper;
use Exception;

/**
 * 文档删除
 */
class Delete extends AbstractCommon {

    public function processRequest(Array & $context) {
        echo($this->_publicDeleteFieldByPostItem($_POST['id'], '`#@__@contact_form`', 'contact_id'));
    }
}
