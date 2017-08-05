<?php

namespace App\Handler\Admin\Setting\Action;

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

        if (!$_POST['sortName'])
            $_POST['sortName'] = 'sa.id';
        if (!$_POST['sortOrder'])
            $_POST['sortOrder'] = 'ASC';

        if (!$_POST['page'])
            $_POST['page'] = 1;
        if (!$_POST['rp'])
            $_POST['rp'] = 10;
        $_sortName = $_POST['sortName'];
        $_sortOrder = $_POST['sortOrder'];
        $_rp = intval($_POST['rp']);
        $_start = (($_POST['page'] - 1) * $_POST['rp']);

        $_where = '0 = 0';
        $_bindParam = array();

        if (isset($_POST['sltDateA']) && $_POST['sltDateA'] && $_POST['sltDateB']) {
            $_where .= ' AND (sa.`add_date` BETWEEN :sltDateA AND :sltDateB)';
            $_bindParam[':sltDateA'] = $_POST['sltDateA'];
            $_bindParam[':sltDateB'] = $_POST['sltDateB'];
        }
        if (isset($_POST['strSearchKeyword']) && $_POST['strSearchKeyword'] != '') {
            $_where .= ' AND (sa.`url` LIKE :strSearchKeyword)';
            $_bindParam[':strSearchKeyword'] = '%' . trim($_POST['strSearchKeyword']) . '%';
        }

        $_table = '`#@__@syslog_action` sa, `#@__@manager_user` mu';
        $_where .= ' AND sa.`user_id` = mu.`user_id`';
        $_total = $this->db->table($_table)->where($_where)->bind($_bindParam)->count();
        
        $_rs = $this->db->select('sa.*, mu.`real_name`')
            ->table($_table)
            ->where($_where)
            ->order($_sortName, $_sortOrder)
            ->limit($_start, $_rp)
            ->bind($_bindParam)
            ->findAll();
        $_rsp = array(
            'totalResults' => $_total,
            'rows' => array()
        );
        $allModel = array_keys($context['adminMap']);
        if ($_total) {
            foreach ($_rs as $m) {
                $_idValue = $m->id;
                //操作内容
                $action = explode('.', $m->title);
                $mode = lcfirst($action[1]);
                $submode = lcfirst($action[2]);
                if(in_array($mode, $allModel)){
                    $option = lcfirst(isset($action[3]) ? $action[3] : '');
                    $model = $context['adminMap'][$mode]['title'] . '-' . $context['adminMap'][$mode]['menu'][$submode]['name'];
                    $operation = $option ? $this->setting['aryOption'][strtolower($option)] : '';
                }else {
                    $submode = strtolower($submode);
                    switch($mode){
                        case 'system':
                            $model = '系统';
                            $operation = $this->setting['aryOption'][$submode];
                            break;
                        case 'editor':
                            $model = '编辑器';
                            $operation =  $this->setting['aryOption'][$submode];
                            break;
                        default:
                            $model = '';
                            $operation = '';
                            break;

                    }
                }

                array_push($_rsp['rows'], array(
                    'id' => $_idValue,
                    'cell' => array(
                        $_idValue,
                        $m->real_name,
                        $model,
                        $operation,
                        $m->url,
                        htmlspecialchars(stripslashes($m->content)),
                        Auxi::getTime($m->add_date)
                    )
                ));
            }
        }
        echo(MsgHelper::json('SUCCESS', '数据返回成功', $_rsp));
    }

}
