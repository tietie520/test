<?php

namespace App\Admin;

if (!defined('IN_PX'))
    exit;

use App\Tools\Auxi;
use Phoenix\Log\Log4p as logger;

/**
 * PageSupport
 * Controller.php, HttpHandler.php继承之后与拦截器共享数据
 * 目前只是在具体的业务类之前执行
 * 考虑到执行效率以及大多数业务不需要在完成后执行，并未实现在业务类之后执行的拦截行为
 * 如果需要执行，需要一个queue在业务之后循环一次
 *
 */
abstract class AbstractCommon {

    protected function __Value($cfg, $setting, $version) {}

//    protected function __Inject($db = null, $cache = null) {}

    public function __InjectData(Array & $data) {
        $this->_data = & $data;
    }

    protected $_data = array();

    /**
     * 选择所属类别
     * @param array $dbOpt 数据库相关参数
     * @param array $optBind select与数据库之间绑定的参数以及默认值 $isShowArchivesList 是否只显示文档列表项
     * @param <type> $selectControl 控件的属性 array('id'=> '', 'name' => '', 'style' => 'width:90%')
     *                              默认为null则使用field作为id及name
     * @param array $optDefaultBind option默认属性
     * @return string
     */

    protected function _selectIDTree($dbOpt = array('table' => '`#@__@table`','where' => '', 'bind' => null,
        'select' => '', 'order' => '', 'sort' => ''),
                                     $optBind = array('value' => 'fieldName', 'text' => 'fieldName',
                                         'selected' => '', 'isShowArchivesList' => false),
                                     $selectControl = null,
                                     $optDefaultBind = array('顶级类别' => '0')
    ) {
        if (!isset($optBind['selected']))
            $optBind['selected'] = null;

        $_out = '<select';

        if (isset($selectControl['disabled']) && false === $selectControl['disabled'])//如果控件不能使用
            $selectControl['disabled'] = 'disabled';
        else {
            unset($selectControl['disabled']);
            if (count($selectControl) == 0) {
                $selectControl = null;
            }
        }
        //如果空间参数为空，则将id设为数据库读取的字段名
        if (is_null($selectControl)) {
            $selectControl['id'] = $optBind['value'];
        }
        //如果只设置了id未设置name，则id name一致
        if (isset($selectControl['id']) && !isset($selectControl['name'])) {
            $selectControl['name'] = $selectControl['id'];
        }
        if (!isset($selectControl['style'])) {
            $selectControl['style'] = 'width:90%';
        }

        foreach ($selectControl as $_k => $_v) {
            $_out .= ' ' . $_k . '=' . $_v;
        }
        $_out .= '>';
        if (count($optDefaultBind) > 0) {
            foreach ($optDefaultBind as $_k => $_v) {
                $_out .= '<option value="' . $_v . '"'
                    . (isset($optBind['selected']) && $optBind['selected'] == $_v ? ' selected' : '')
                    . (is_numeric($_v) ? Auxi::getDeepColor(intval($_v)) : '')
                    . '>├ ' . $_k . '</option>';
            }
        }
        //$this->db->debug();
        if (isset($dbOpt['limit'])) {
            $this->db->limit(isset($dbOpt['limit']) ? $dbOpt['limit'] : null);
        }
        $_rs = $this->db->select(isset($dbOpt['select']) ? $dbOpt['select'] : '*')
            ->table($dbOpt['table'])
            ->where(isset($dbOpt['where']) ? $dbOpt['where'] : null)
            ->order(isset($dbOpt['order']) ? $dbOpt['order'] : 'id_tree',
                isset($dbOpt['sort']) ? $dbOpt['sort'] : 'ASC')
            ->bind(isset($dbOpt['bind']) ? $dbOpt['bind'] : array())
            ->findAll();
        if (count($_rs) > 0) {
            foreach ($_rs as $m) {
                if (!isset($m->is_part)) {
                    $m->is_part = 0;
                }
                if (!isset($m->level)) {
                    $m->level = 1;
                }

                if ((isset($optBind['isShowArchivesList']) && (intval($m->is_part) == 0 ||
                            intval($m->level) == 1)) ||
                    (isset($optBind['isShowArchivesList']) && false === $optBind['isShowArchivesList']) ||
                    !isset($optBind['isShowArchivesList'])) {
                    $_out .= '<option value="' . $m->$optBind['value'] . '"';

                    if ($m->$optBind['value'] == $optBind['selected'])
                        $_out .= ' selected';

                    $_out .= Auxi::getDeepColor($m->level) . '>'
                        . str_repeat('&nbsp;&nbsp;', intval($m->level))
                        . '└ ' . $m->$optBind['text'] . '</option>';
                }
            }
        }
        $_out .= '</select>';

        return $_out;
    }

    protected function _boolCanReadData() {
        if (isset($_GET['id']) && $_GET['id'] != '' && in_array($_GET['action'], array('view', 'edit', 'reply'))) {
            return true;
        }
        return false;
    }

    /**
     * 获取排序
     * @param type|string $dt
     * @param type|string $field
     * @return type
     */
    protected function _getSort($dt = 'category', $field = 'sort') {
        //$this->db->debug = true;
        return (intval($this->db->field("MAX({$field})")->table("`#@__@{$dt}`")->find()) + 1);
    }

    /**
     *
     * @param <type> $roleId
     * @return string
     */
    protected function _getManagerRoleId($roleId) {
        $_tempOpt = '<select name="role_id" id="role_id">';
        $_rs = $this->db->select('`role_id`, `role_name`')
            ->table('`#@__@manager_role`')
            ->order('role_id', 'ASC')
            ->findAll();
        foreach ($_rs as $m) {
            $_tempOpt .= '<option value="' . $m->role_id . '"';
            if (intval($m->role_id) == intval($roleId)) {
                $_tempOpt .= ' selected="selected"';
            }
            $_tempOpt .= '>' . stripslashes($m->role_name) . '</option>';
        }
        $_tempOpt .= '</select>';
        return $_tempOpt;
    }

    protected function _getArchivesTags($id) {
        return implode(',', $this->repoArc->getTagsList($id));
    }

    protected function _getArchivesTagsDocument() {
        $_r = '<ol>';
        $_tempRs = $this->repoArc->getTags();
        foreach ($_tempRs as $m) {
            $_r .= '<li><span>' . $m->tags_text
                . '</span>(' . $m->total
                . ')<a href="javascript:void(0);" onclick="Tools.deleteTag('
                . $m->tags_id . ', this);">&times;</a></li>';
        }
        $_r .= '</ol>';
        return $_r;
    }

    public function __get($name) {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }
        return null;
    }

    public function __set($name, $value) {
        return $this->_data[$name] = $value;
    }

}
