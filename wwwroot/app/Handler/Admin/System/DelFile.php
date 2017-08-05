<?php

namespace App\Handler\Admin\System;

if (!defined('IN_PX'))
    exit;

use App\Service\UPFile;
use Phoenix\Routing\IHttpHandler;
use Phoenix\Support\MsgHelper;

/**
 * DelFile
 */
class DelFile implements IHttpHandler {

    private function __Handler() {}

    protected function __Inject(UPFile $upFile) {}

    public function processRequest(Array & $context) {
        if (isset($_POST['delFileName']) && $this->upFile->deleteFile($_POST['delFileName'], $_POST['uploadType'])) {
            echo(MsgHelper::json('SUCCESS'));
        } else {
            echo(MsgHelper::json('ERROR'));
        }
    }

}
