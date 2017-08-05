<?php

namespace Phoenix\PXPDO;

if (!defined('IN_PX'))
    exit;

//use Phoenix\Log\Log4p as logger;

//mysql数据库操作
class Mysql extends AbstractDAO {

    const VERSION = '1.1.3';

    public function replaceInto() {
        if (count($this->_sqlParamStorage) > 0) {
            $_fields = array();
            $_values = array();
            $_parseSql = array();
            foreach ($this->_sqlParamStorage['row'] as $_k => $_v) {
                array_push($_fields, $_k);
                array_push($_values, $_v);
                array_push($_parseSql, "$_k = $_v");
            }
            $_sql = 'REPLACE INTO ' . $this->_sqlParamStorage['table'] . ' ('
                . implode(', ', $_fields) . ') VALUES ('
                . implode(', ', $_values) . ')';

            $_bindParam = $this->_autoFillBindParam(implode(' ', $_parseSql),
                $this->_sqlParamStorage['bind']);

            unset($_fields, $_values, $_parseSql);
            $_fields = $_values = $_parseSql = null;

            return $this->sqlInsert($_sql, $_bindParam);
        }
        return false;
    }

    public function insertUpdate($aryRemoveUpdateField = null) {
        if (count($this->_sqlParamStorage) > 0) {
            $_fields = array();
            $_values = array();
            $_update = array();
            $_parseSql = array();
            $_bool = false; //排除第一项主键
            foreach ($this->_sqlParamStorage['row'] as $_k => $_v) {
                array_push($_fields, $_k);
                array_push($_values, $_v);
                array_push($_parseSql, "$_k = $_v");

                if ($_bool && (is_null($aryRemoveUpdateField) || !in_array($_k, $aryRemoveUpdateField))) {
                    array_push($_update, strcmp('?', $_v) == 0 ? "$_k = VALUES($_k)" : "$_k = $_v");
                }
                $_bool = true;
            }
            $_sql = 'INSERT INTO ' . $this->_sqlParamStorage['table'] . ' ('
                . implode(', ', $_fields) . ') VALUES ('
                . implode(', ', $_values) . ') ON DUPLICATE KEY UPDATE '
                . implode(', ', $_update);

            $_bindParam = $this->_autoFillBindParam(implode(' ', $_parseSql),
                $this->_sqlParamStorage['bind']);

            unset($_fields, $_values, $_update, $_parseSql);
            $_fields = $_values = $_update = $_parseSql = $_bool = null;

            return $this->sqlInsert($_sql, $_bindParam);
        }
        return false;
    }

    public function findAll() {
        if (count($this->_sqlParamStorage) > 0) {
            return $this->prepareBindLevel2Cache(
                $this->_sqlParamStorage['select']
                . ' FROM ' . $this->_sqlParamStorage['table']
                . $this->_sqlParamStorage['where']
                . $this->_sqlParamStorage['group']
                . $this->_sqlParamStorage['having']
                . $this->_sqlParamStorage['order']
                . $this->_sqlParamStorage['limit'],
                $this->_sqlParamStorage['bind']);
        }
        return false;
    }

    public function find() {
        if (count($this->_sqlParamStorage) > 0) {
//         logger::debug($this->_sqlParamStorage);
            switch ($this->_operation) {
                case 'READ' ://取一条记录
                    return $this->sqlDataReadOne(
                        $this->_sqlParamStorage['select']
                        . ' FROM ' . $this->_sqlParamStorage['table']
                        . $this->_sqlParamStorage['where']
                        . $this->_sqlParamStorage['group']
                        . $this->_sqlParamStorage['having']
                        . $this->_sqlParamStorage['order']
                        . ' LIMIT 0, 1',
                        $this->_sqlParamStorage['bind']);
                    break;
                case 'READ_COLUMN' :
                    return $this->sqlDataReadField(
                        $this->_sqlParamStorage['field']
                        . ' FROM ' . $this->_sqlParamStorage['table']
                        . $this->_sqlParamStorage['where']
                        . $this->_sqlParamStorage['group']
                        . $this->_sqlParamStorage['having']
                        . $this->_sqlParamStorage['order']
                        . ' LIMIT 0, 1',
                        $this->_sqlParamStorage['bind']);
                    break;
            }
        }
        return false;
    }

    public function count() {
        if (count($this->_sqlParamStorage) > 0) {
            return $this->sqlDataReadField(
                (isset($this->_sqlParamStorage['field']) ? $this->_sqlParamStorage['field'] : 'SELECT COUNT(*)')
                . ' FROM ' . $this->_sqlParamStorage['table']
                . $this->_sqlParamStorage['where']
                . $this->_sqlParamStorage['order']
                . $this->_sqlParamStorage['limit'],
                $this->_sqlParamStorage['bind']);
        }
        return 0;
    }

    protected function _compatible($sql) {
        return $sql;
    }

    protected function _limit($start, $pageSize) {
        $_limit = '';
        $_sqlChip = ' LIMIT ';
        if (!is_null($pageSize)) {
            $_limit = $_sqlChip . intval($start) . ', ' . intval($pageSize);
        } else if (intval($start) > 0) {
            $_limit = $_sqlChip . '0, ' . intval($start);
        }
        return $_limit;
    }

    protected function _fixedBindParam($bindParam) {
        return $bindParam;
    }

    public function lastInsertId() {
        return $this->_pdo->lastInsertId();
    }

    public function exists() {
        return $this->count() > 0;
    }

    public function close() {
        $this->__destruct();
    }

}
