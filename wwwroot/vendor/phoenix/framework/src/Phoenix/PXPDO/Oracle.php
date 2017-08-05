<?php

namespace Phoenix\PXPDO;

if (!defined('IN_PX'))
    exit;

//use Phoenix\Log\Log4p as logger;

//oracle数据库操作
class Oracle extends AbstractDAO {

    const VERSION = '0.0.1';

    /**
     * 替换更新，更新时默认更新所有提交字段，同mysql功能
     * @return boolean
     */
    public function replaceInto() {
        return $this->insertUpdate();
    }

    /**
     * 排除指定字段的更新
     * @param type $aryRemoveUpdateField 更新时需要排除的字段
     * @return boolean
     */
    public function insertUpdate($aryRemoveUpdateField = null) {
        if (count($this->_sqlParamStorage) > 0) {
            $_fields = array();
            $_values = array();
            $_parseSql = array();
            $_updateValues = array();
            $_id = null;
            $_removeUpdateParamIndex = array(); //更新排除字段的索引
            $_i = 0;
            foreach ($this->_sqlParamStorage['row'] as $_k => $_v) {
                if (is_null($_id)) {
                    $_id = $_k;
                } else {
                    if (!is_null($aryRemoveUpdateField) && in_array($_k, $aryRemoveUpdateField)) {
                        $_removeUpdateParamIndex[] = $_i;
                    } else {
                        array_push($_updateValues, "a.$_k = $_v");
                    }
                }
                array_push($_fields, 'a.' . $_k);
                array_push($_values, $_v);
                array_push($_parseSql, "a.$_k = $_v");
                $_i++;
            }
            $_insertFields = implode(', ', $_fields);
            $_insertValues = implode(', ', $_values);
            $_updateSql = implode(', ', $_updateValues);
            $_sql = "MERGE INTO {$this->_sqlParamStorage['table']} a USING "
                . "(SELECT ? AS {$_id} FROM DUAL) b ON (a.{$_id} = b.{$_id}) "
                . "WHEN MATCHED THEN UPDATE SET {$_updateSql} "
                . "WHEN NOT MATCHED THEN INSERT ({$_insertFields}) VALUES ({$_insertValues})";

            //纯粹根据insert解析出字段对应的值
            $_bindParam = $this->_autoFillBindParam(implode(' ', $_parseSql),
                $this->_sqlParamStorage['bind']);

            unset($_fields, $_values, $_parseSql, $_updateValues);
            $_fields = $_values = $_parseSql = $_updateValues = null;
            $_updateParam = $_bindParam;
            //排除法
            if (count($_removeUpdateParamIndex) > 0) {
                foreach ($_removeUpdateParamIndex as $_index) {
                    unset($_updateParam[$_index]);
                }
            }
            $_bindParam = array_merge($_updateParam, $_bindParam);
            return $this->sqlInsert($_sql, $_bindParam);
        }
        return false;
    }

    public function findAll() {
        if (count($this->_sqlParamStorage) > 0) {
            $_sql = $this->_sqlParamStorage['select']
                . ' FROM ' . $this->_sqlParamStorage['table']
                . $this->_sqlParamStorage['where']
                . $this->_sqlParamStorage['group']
                . $this->_sqlParamStorage['having']
                . $this->_sqlParamStorage['order'];
            if (!is_null($this->_sqlParamStorage['limit'])) {
                $_wrapFlag = false;
                list($_start, $_end) = $this->_sqlParamStorage['limit'];
                $_start = intval($_start);
                if (!is_null($_end)) {
                    $_end = intval($_end);
                    $_end += $_start;
                    $_wrapFlag = true;
                } else if ($_start > 0) {
                    $_end = $_start;
                    $_start = 0;
                    $_wrapFlag = true;
                }
                if ($_wrapFlag) {
                    $_sql = "SELECT * FROM (SELECT LIMIT_A.*, rownum LIMIT_R FROM ({$_sql})"
                        . " LIMIT_A WHERE rownum <= {$_end}) LIMIT_B WHERE LIMIT_R > {$_start}";
                }
            }
            return $this->prepareBindLevel2Cache($_sql, $this->_sqlParamStorage['bind']);
        }
        return false;
    }

    public function find() {
        if (count($this->_sqlParamStorage) > 0) {
//         \logger::debug($this->_sqlParamStorage);
            switch ($this->_operation) {
                case 'READ' ://取一条记录
                    return $this->sqlDataReadOne(
                        $this->_sqlParamStorage['select']
                        . ' FROM ' . $this->_sqlParamStorage['table']
                        . $this->_sqlParamStorage['where']
                        . $this->_sqlParamStorage['group']
                        . $this->_sqlParamStorage['having']
                        . $this->_sqlParamStorage['order']
                        . ($this->_sqlParamStorage['where'] != '' ? ' AND' : '') . ' ROWNUM = 1',
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
                        . ($this->_sqlParamStorage['where'] != '' ? ' AND' : '') . ' ROWNUM = 1',
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
                . $this->_sqlParamStorage['order'],
                $this->_sqlParamStorage['bind']);
        }
        return 0;
    }

    protected function _compatible($sql) {
        $sql = str_replace(array('`', $this->_dsn['sequencer']),
            array('"', "{$this->_dsn['user']}_SEQ.NEXTVAL"),
            $sql);
        return strtoupper($sql);
    }

    protected function _limit($start, $pageSize) {
        return array($start, $pageSize);
    }

    /**
     * 修正oracle中插入空值
     * @param type $bindParam
     * @return type
     */
    protected function _fixedBindParam($bindParam) {
        foreach ($bindParam as $_k => & $_v) {
            if (!is_numeric($_v) && empty($_v)) {
                $_v = ' ';
            }
        }
        return $bindParam;
    }

    public function lastInsertId() {
        return $this->sqlDataReadField("SELECT {$this->_dsn['user']}_SEQ.CURRVAL"
            . " AS VALUE FROM DUAL");
    }

    public function exists() {
        return $this->count() > 0;
    }

    public function close() {
        $this->__destruct();
    }

}
