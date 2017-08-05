<?php

namespace App\Handler\Admin\System;

if (!defined('IN_PX'))
    exit;

use App\Service\UPFile;
use Phoenix\Routing\IHttpHandler;
use Phoenix\Support\MsgHelper;
use Phoenix\Log\Log4p as logger;

/**
 * 后台图片上传
 */
class Upload implements IHttpHandler {

    private function __Handler() {}

    private function __Inject(UPFile $upFile) {}

    public function processRequest(Array & $context) {
        //$this->pushSetting(); //加载配置文件
        //logger::debug($_POST['uploadType']);
        $_upReturn = $this->upFile->upload('getFileToUpload', $_POST['importMode'], $_POST['uploadType']);
        if ($this->upFile->noneErr) {
            echo MsgHelper::json('SUCCESS', $_upReturn['name'], json_encode($_upReturn));
        } else {
            echo MsgHelper::json('ERROR', $_upReturn);
        }
    }

}
