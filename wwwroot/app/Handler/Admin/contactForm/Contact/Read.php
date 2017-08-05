<?php

namespace App\Handler\Admin\ContactForm\Contact;

if (!defined('IN_PX'))
    exit;

use App\Handler\Admin\AbstractCommon;
use App\Tools\Auxi;
use Phoenix\Support\MsgHelper;
use Phoenix\Log\Log4p as logger;

/**
 * 读取
 */
class Read extends AbstractCommon {

    public function processRequest(Array & $context) {
        $this->_pushSetting();

        $_start = ($_POST['page'] - 1) * $_POST['rp'];

        $_where = '0 = 0';
        $_bindParam = array();

        if (isset($_POST['sltDateA']) && $_POST['sltDateA'] && $_POST['sltDateB']) {
            $_where .= ' AND (a.`add_date` BETWEEN :sltDateA AND :sltDateB)';
            $_bindParam[':sltDateA'] = $_POST['sltDateA'];
            $_bindParam[':sltDateB'] = $_POST['sltDateB'];
        }

        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (a.`contact_json` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }

        $_table = '`#@__@contact_form` a';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();
//        $this->db->debug();
        $_rs = $this->db->select('a.*')
            ->table($_table)
            ->where($_where)
//            ->order('a.is_status DESC, ' . $_POST['sortName'], $_POST['sortOrder'])
            ->limit($_start, $_POST['rp'])
            ->bind($_bindParam)
            ->findAll();

        $_rsp = array(
            'totalResults' => $_total,
            'rows' => array()
        );
        if ($_total) {
            foreach ($_rs as $m) {
                $_idValue = $m->contact_id;
                $_aryContact = json_decode($m->contact_json);
                $_str = '';
                if (count($_aryContact) > 0) {
                    foreach ($_aryContact as $_v) {
                        $_str .= '<b>'.$_v->name.':</b> ' . $_v->value . '&nbsp;&nbsp;&nbsp;&nbsp;';
                    }
                }
                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        $_str,
                        Auxi::getTime($m->add_date)
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
