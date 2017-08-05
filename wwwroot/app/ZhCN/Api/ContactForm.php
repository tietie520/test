<?php

namespace App\ZhCN\Api;

if (!defined('IN_PX'))
    exit;

use App\Repository;
use Phoenix\Support\MsgHelper;
use Phoenix\Log\Log4p as logger;

class ContactForm {

    private function __RestController() {}

    private function __Route($value = '/api') {}

    protected function __Inject($session, Repository\ContactForm $contactForm) {}

    /**
     * 首页联系表单接品
     * @return array
     */
    public function contactForm() {
        $_hash = $this->session->contactHash['hash'];
        $_submitTime = $this->session->contactHash['time'];

        if ($_hash == $_POST['contactHash'] && $_submitTime == 0) {
            if (isset($_POST['contactForm']) && $_POST['contactForm'] != '') {
                if ($flag = $this->contactForm->save($_POST['contactForm'])) {
                    $this->session->contactHash = array('time' => 1);
                    return MsgHelper::err(0);
                } else {
                    return  MsgHelper::err(10003, '系统繁忙!');
                }
            } else {
                return MsgHelper::err(10002, '非法操作!');
            }
        } else {
            return MsgHelper::err(10002, '不能重复提交!');
        }
    }
}
