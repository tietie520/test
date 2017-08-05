<?php

namespace App\Repository\Impl;

if (!defined('IN_PX'))
    exit;

use App\Repository\ContactForm;

class ContactFormImpl implements ContactForm {

    //仓储层组件
    private function __Repository($value = 'contactForm') {}

    private function __Inject($db) {}

    public function save($contactJson) {
        $_time = time();
        return $this->db->table('`#@__@contact_form`')
            ->row(array(
                '`contact_json`' => '?',
                '`add_date`' => '?',
                '`release_date`' => '?'
            ))
            ->bind(array($contactJson, $_time, $_time))
            ->save();
    }

}
