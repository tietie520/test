<?php

namespace Phoenix\PXPDO;

if (!defined('IN_PX'))
    exit;

use PDO;
use Phoenix\Log\Log4p as logger;

/**
 * AbstractDAO装饰类抽象
 * upated:2014-01-07 增加inner left right
 * @author
 */
abstract class AbstractDecorator extends AbstractDAO {

    protected $_pdo = null;
    private $_arySqlParamStorage = array(); //sql,传入绑定参数的链式操作的暂存器，每次执行后重置为空

    public function __construct(AbstractDAO & $pdo) {
        $this->_pdo = & $pdo;
    }

    /**
     * 访问器链式操作中始终修改寄存器的最后一项，
     * 可以在chains()方法开辟的内存中不污染sql语句
     * @param type|string $operation
     * @param type $value
     */
    private function _chains($operation = 'default', $value = null) {
        if (count($this->_arySqlParamStorage) == 0) {
            array_push($this->_arySqlParamStorage, array());
        }

        if ($value) {
            end($this->_arySqlParamStorage);
            $_key = key($this->_arySqlParamStorage);
            if (in_array($operation, array('inner', 'left', 'right'))) {
                $this->_arySqlParamStorage[$_key]['table'] .= $value;
            } else {
                $this->_arySqlParamStorage[$_key][$operation] = $value;
            }
            unset($value);
            $value = null;
//			$_current = end($this->_arySqlParamStorage);
//			$_current[$operation] = $value;
//			array_pop($this->_arySqlParamStorage);
//			array_push($this->_arySqlParamStorage, $_current);
//			logger::debug($this->_arySqlParamStorage);
        }
    }

    /**
     * 避免链式操作的方法中又嵌套一个sql链式操作导致的覆盖上一次的sql值
     * 如：$this->db->table(...)->where(...)->bind(
     * 		$this->db->select(...)->table(...)->find(...)
     * )->update()
     * 此时bind()中的访问器的sql会覆盖上一个(即update()操作中)访问器的sql
     * 导致无法响应结果
     * 正确的处理方式有三种
     * 1】嵌套的访问器加入chains()方法注明
     * $this->db->table(...)->where(...)->bind(
     * 		$this->db->chains()->select(...)->table(...)->find(...)
     * )->update()
     * 此时嵌套的访问器会开辟一块内存空间专门存放当前的sql，从而避免冲突，
     * 嵌套多个则嵌套的多个都必须加chains方法注明
     * 2】将嵌套的值移到访问器的外部单独获取，访问器中不出现访问器的嵌套
     * 3】直接使用option()注入
     * @return \Phoenix\PXPDO\AbstractDecorator
     */
    public function chains() {
        array_push($this->_arySqlParamStorage, array());
        return $this;
    }

    public function select($str = '*') {
        $this->_chains('select', $str);
        return $this;
    }

    public function field($field) {
        $this->_chains('field', $field);
        return $this;
    }

    public function table($str) {
        $this->_chains('table', $str);
        return $this;
    }

    public function inner($table, $on) {
        if ($table && $on) {
            $this->_chains('inner', ' INNER JOIN ' . $table . ' ON ' . $on);
        }
        return $this;
    }

    public function left($table, $on) {
        if ($table && $on) {
            $this->_chains('left', ' LEFT JOIN ' . $table . ' ON ' . $on);
        }
        return $this;
    }

    public function right($table, $on) {
        if ($table && $on) {
            $this->_chains('right', ' RIGHT JOIN ' . $table . ' ON ' . $on);
        }
        return $this;
    }

    public function where($str) {
        if ($str) {
            $this->_chains('where', $str);
        }
        return $this;
    }

    public function group($str) {
        if ($str) {
            $this->_chains('group', $str);
        }
        return $this;
    }

    public function having($str) {
        if ($str) {
            $this->_chains('having', $str);
        }
        return $this;
    }

    public function order($str, $sort = 'DESC') {
        if ($str) {
            $this->_chains('order', $str . ' ' . $sort);
        }
        return $this;
    }

    public function limit($start, $end = null) {
        $this->_chains('limit', array($start, $end));
//        if (!is_null($end)) {
//            $this->_chains('limit', $start . ', ' . $end);
//        } else if (intval($start) > 0) {
//            $this->_chains('limit', '0, ' . $start);
//        }
        return $this;
    }

    public function bind(Array $bindParam) {
        if (count($bindParam) > 0) {
            $this->_chains('bind', $bindParam);
            unset($bindParam);
            $bindParam = null;
        }
        return $this;
    }

    public function row(Array $row) {
        if (count($row) > 0) {
            $this->_chains('row', $row);
            unset($row);
            $row = null;
        }
        return $this;
    }

    public function option(Array $option) {
        array_push($this->_arySqlParamStorage, $option);
        unset($option);
        $option = null;
        return $this;
    }

    /**
     * 避免访问器嵌套导致的sql污染
     * 始终注入最后一次的sql语句，并且使用完成后删除
     * @return \Phoenix\PXPDO\AbstractDecorator
     */
    private function _set() {
        //logger::debug($this->_arySqlParamStorage);
        $this->_pdo->option(end($this->_arySqlParamStorage));
        array_pop($this->_arySqlParamStorage);
        return $this;
    }

    public function save() {
        return $this->_set()->_pdo->save();
    }

    public function update() {
        return $this->_set()->_pdo->update();
    }

    public function delete() {
        return $this->_set()->_pdo->delete();
    }

    public function findAll() {
        return $this->_set()->_pdo->findAll();
    }

    public function find() {
        return $this->_set()->_pdo->find();
    }

    public function count() {
        return $this->_set()->_pdo->count();
    }

    public function exists() {
        return $this->_set()->_pdo->exists();
    }

    public function replaceInto() {
        return $this->_set()->_pdo->replaceInto();
    }

    public function insertUpdate($aryRemoveUpdateField = null) {
        return $this->_set()->_pdo->insertUpdate($aryRemoveUpdateField);
    }

    protected function _compatible($sql) {
        return null;
    }

    protected function _limit($start, $pageSize) {
        return null;
    }

    protected function _fixedBindParam($bindParam) {
        return null;
    }

    public function toString() {
        return $this->_pdo->toString();
    }

    public function total() {
        return $this->_pdo->total();
    }

    public function debug() {
        $this->_pdo->debug();
        return $this;
    }

    public function cacheable($expires = 0) {
        $this->_pdo->cacheable($expires);
        return $this;
    }

    public function nonCacheable() {
        $this->_pdo->nonCacheable();
        return $this;
    }

    public function mode($style = PDO::FETCH_OBJ) {
        $this->_pdo->mode($style);
        return $this;
    }

    public function useSqlStore() {
        $this->_pdo->useSqlStore();
        return $this;
    }

    public function getSqlStore() {
        return $this->_pdo->getSqlStore();
    }

    public function prepareBindLevel2Cache($sql, $bindParam = null, $operation = 'READ') {
        return $this->_pdo->prepareBindLevel2Cache($sql, $bindParam, $operation);
    }

    public function prepare($sql, $bindParam = null, $operation = 'READ') {
        return $this->_pdo->prepare($sql, $bindParam, $operation);
    }

    public function query($sql) {
        return $this->_pdo->query($sql);
    }

    public function exec($sql) {
        return $this->_pdo->exec($sql);
    }

    public function procedure($proc, $operation = 'excute') {
        $this->_chains('procedure', $proc);
        $this->_chains('operation', $operation);
        return $this;
    }

    public function call() {
        return $this->_set()->_pdo->call();
    }

    public function sqlInsert($sql, $bindParam = null) {
        return $this->_pdo->sqlInsert($sql, $bindParam);
    }

    public function sqlExecute($sql, $bindParam = null) {
        return $this->_pdo->sqlExecute($sql, $bindParam);
    }

    public function sqlDataReadOne($sql, $bindParam) {
        return $this->_pdo->sqlDataReadOne($sql, $bindParam);
    }

    public function sqlDataReadField($sql, $bindParam = null) {
        return $this->_pdo->sqlDataReadField($sql, $bindParam);
    }

    public function lastInsertId() {
        return $this->_pdo->lastInsertId();
    }

    public function beginTransaction() {
        return $this->_pdo->beginTransaction();
    }

    public function commit() {
        return $this->_pdo->commit();
    }

    public function rollBack() {
        return $this->_pdo->rollBack();
    }

    public function sequence64($table) {
        $_rs = $this->procedure('#@__@sequence64(?)', 'field')->bind(array(
            'prefix' => array($this->_pdo->getPrefix() . 'sequence64_' . $table, PDO::PARAM_STR, 30)
        ))->call();
        return intval($_rs['result']);
    }

    public function close() {
        return $this->_pdo->close();
    }

}
