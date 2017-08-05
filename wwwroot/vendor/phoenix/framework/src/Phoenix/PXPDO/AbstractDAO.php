<?php

namespace Phoenix\PXPDO;

if (!defined('IN_PX'))
    exit;

use PDO;
use PDOException;
use Phoenix\Exception\FormatException;
use Phoenix\Log\Log4p as logger;

/**
 * 数据库抽象类及大部分访问方法
 * 继承者必须实现reader,readOne,readField,close方法
 * 由于多个数据库sql的差异，以上访问器需要重写，其他方法通用
 * v1.3.4 访问器重写，删除reader等方法，使用更明晰的sql语句及访问接口
 * v1.3.5 链式操作移至装饰器类中，底层只保留一个option()注入sql参数数组
 * v1.3.6 连接字符串增加端口 port
 */
abstract class AbstractDAO {

    const VERSION = '1.3.9';

    protected $_pdo = null; //数据库连接
    private $_pdoStatement = null; //pdo执行返回的对象
    private $_debug = false; //是否调试sql
    private $_cache = null; //缓存调用
    private $_cachePack = '~pdo/'; //数据库2级缓存的目录，支持多数据源
    private $_total = 0; //数据库访问次数统计
    private $_canUseLevel2cache = false; //是否可以使用2级缓存
    private $_cacheable = false; //本次查询应用缓存
    private $_nonCacheable = false; //本次调用不使用缓存
    private $_expires = 0; //二级缓存时间，0为默认且为不超时
    private $_fetchMode = PDO::FETCH_OBJ; //pdo展现数据的方式
    private $_useSqlStore = false;
    private $_level2TopLimit = 0; //二级缓存上限，返回值超过这个值的才进行缓存，低于此值定义为不具备缓存价值
    private $_persistent = false;
    protected $_sqlParamStorage = null; //sql,传入绑定参数链式操作的暂存器，每次执行后重置为空
    protected $_sqlStore = null;//所有执行的sql语句
    protected $_operation = ''; //当前的操作类型 如 READ READ_COLUMN
    protected $_dsn = null; //dsn配置
    protected $_prefix = '#@__@';

    /**
     * @param $dsn
     * @param $cache
     * @throws FormatException
     */
    public function __construct(& $dsn, & $cache) {
        if ($dsn) {
            $this->_dsn = & $dsn;
            $this->_cachePack .= md5($this->_dsn['dbName']) . '/';
            //先判断能否使用2级缓存
            if ($this->_dsn['level2cache'] && !is_null($cache)) {
                $this->_canUseLevel2cache = true;
                $this->_cache = & $cache;
            } else {
                $this->_canUseLevel2cache = false;
            }
            if (isset($this->_dsn['level2TopLimit'])) {
                $this->_level2TopLimit = $this->_dsn['level2TopLimit'];
            }
        } else {
            throw new FormatException('undefined database dsn.can not connect database.', '0x00002001');
        }
    }

    /**
     * 初始化未注册的参数
     * @param type $option
     */
    private function _initParameter(& $option) {
        if (isset($option['procedure'])) {
            return;
        }
        $_options = array(
            'where' => '',
            'group' => '',
            'having' => '',
            'order' => '',
            'limit' => '',
            'row' => null,
            'table' => '',
            'bind' => null
        );
        foreach ($_options as $_k => $_v) {
            if (!isset($option[$_k])) {
                switch ($_k) {
                    case 'row':
                    case 'bind' :
                        $option[$_k] = $_v;
                        break;
                    default :
                        $option[$_k] = $_v;
                        break;
                }
            }
        }
    }

    /**
     * 注入sql参数数组
     * @param array $option 如：array('select' => '*', 'table' => '...')
     * @return \Phoenix\PXPDO\AbstractDAO
     */
    public function option(Array $option) {
        $this->_initParameter($option);

        foreach ($option as $_k => $_v) {
            switch ($_k) {
                case 'select' :
                    $this->_operation = 'READ';
                    $option[$_k] = 'SELECT ' . $_v;
                    break;
                case 'field' :
                    $this->_operation = 'READ_COLUMN';
                    $option[$_k] = 'SELECT ' . $_v;
                    break;
                case 'where' :
                    $option[$_k] = $_v ? ' WHERE ' . $_v : '';
                    break;
                case 'group' :
                    $option[$_k] = $_v ? ' GROUP BY ' . $this->_strCheck($_v) : '';
                    break;
                case 'having' :
                    $option[$_k] = $_v ? ' HAVING ' . $this->_strCheck($_v) : '';
                    break;
                case 'order' :
                    $option[$_k] = $_v ? ' ORDER BY ' . $this->_strCheck($_v) : '';
                    break;
                case 'limit' :
                    $option[$_k] = $_v && is_array($_v) ? $this->_limit(intval($_v[0]), intval($_v[1])) : null;
//                    $option[$_k] = $_v ? ' LIMIT ' . $_v : '';
                    break;
            }
        }
        $this->_sqlParamStorage = $option;
        unset($option);
        $option = null;
        return $this;
    }

    private function _strCheck($str) {
        if(!get_magic_quotes_gpc()) {
            $str = addslashes($str);
        }
//        $str = str_replace('_', '\_', $str);
        $str = str_replace('%', '\%', $str);

        return $str;
    }

    /**
     * 从option中获取的数据插入库
     * @return boolean
     */
    public function save() {
        if (count($this->_sqlParamStorage) > 0 && !is_null($this->_sqlParamStorage['row'])) {
            $_fields = array();
            $_values = array();
            $_parseSql = array();
            foreach ($this->_sqlParamStorage['row'] as $_k => $_v) {
                //不使用全局序列同时命中序列发生器则忽略
                if (!$this->_dsn['useSequencer'] && strcmp($_v, $this->_dsn['sequencer']) == 0) {
                    continue;
                }
                $_k = '`' . trim($_k, '`') . '`';
                array_push($_fields, $_k);
                array_push($_values, $_v);
                array_push($_parseSql, "$_k = $_v");
            }
            $_sql = 'INSERT INTO ' . $this->_sqlParamStorage['table'] . ' ('
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

    /**
     * 更新，依赖option()
     * @return boolean
     */
    public function update() {
        if (count($this->_sqlParamStorage) > 0 && !is_null($this->_sqlParamStorage['row'])) {
            $_update = array();
            foreach ($this->_sqlParamStorage['row'] as $_k => $_v) {
                $_k = '`' . trim($_k, '`') . '`';
                array_push($_update, "$_k = $_v");
            }
            $_sql = 'UPDATE ' . $this->_sqlParamStorage['table'] . ' SET '
                . implode(', ', $_update)
                . $this->_sqlParamStorage['where'];

            unset($_update);
            $_update = null;

            return $this->sqlExecute($_sql, $this->_sqlParamStorage['bind']);
        }
        return false;
    }

    /**
     * 删除，依赖option()
     * @return boolean
     */
    public function delete() {
        if (count($this->_sqlParamStorage) > 0) {
            return $this->sqlExecute('DELETE FROM '
                . $this->_sqlParamStorage['table']
                . $this->_sqlParamStorage['where'],
                $this->_sqlParamStorage['bind']);
        }
        return false;
    }

    /**
     * 读取符合条件的所有记录，依赖option()
     */
    abstract function findAll();

    /**
     * 只读取一条记录，使用select则读取一条记录集，使用field则读取一个字段，依赖option()
     */
    abstract function find();

    /**
     * 统计，依赖option()
     * 只读取单字段，与field()配合
     * 如果使用了聚合函数则应该使用select()->group()->having()->find()配合
     */
    abstract function count();

    /**
     * 测试数据是否存在
     * count的别名方法，依赖option()
     */
    abstract function exists();

    /**
     * 如果记录不存在则插入新记录，存在则替换
     * 表中必须存在主键，同时主键放在第一个位置
     * mysql有REPLACE函数，其他数据库需要进行判断
     * 注：主键必须存在且为第一列(约定)
     *     mysql是删除后插入新数据，如果主键自增则不要使用
     *     如果需要保留当前数据须使用 insertUpadate，依赖option()
     */
    abstract function replaceInto();

    /**
     * 如果记录不存在则插入新记录，存在则替换
     * 表中必须存在主键，同时主键放在第一个位置
     * 本函数不会删除原有数据，主键自增不会有影响
     * 如果更新时如果无变化可能返回值是0
     * 注：主键必须存在且为第一列(约定)，依赖option()
     */
    abstract function insertUpdate($aryRemoveUpdateField = null);

    /**
     * sql兼容
     */
    abstract protected function _compatible($sql);

    abstract protected function _limit($start, $pageSize);

    abstract protected function _fixedBindParam($bindParam);

    /**
     * 取得上一步 INSERT 操作产生的 ID
     */
    abstract function lastInsertId();

    /**
     * 输出数据库连接状态
     */
    public function toString() {
        //die(var_dump($this->_dsn));
        $this->_lazyConn();
        echo($this->_dsn['hostName'] . ' : '
            . $this->_pdo->query('SELECT DATABASE()')->fetchColumn()
            . ' IsOk');
    }

    /**
     * 返回访问数据库次数
     * @return type
     */
    public function total() {
        return $this->_total;
    }

    /**
     * 输出sql到控制台
     * @return \Phoenix\PXPDO\AbstractDAO
     */
    public function debug() {
        $this->_debug = true;
        return $this;
    }

    /**
     * 立即缓存当前数据集
     * 每次执行完一次集合缓存，缓存开关会立即关闭
     * 可做链式操作，推荐。如：$this->db->cacheable()->reader..
     * @return \Phoenix\PXPDO\AbstractDAO
     */
    public function cacheable($expires = 0) {
        $this->_cacheable = true;
        $this->_expires = $expires;
        return $this;
    }

    /**
     * 本次操作不使用2级缓冲区。例如update更新小字段时中止掉(view_total的统计等)
     * @return \Phoenix\PXPDO\AbstractDAO
     */
    public function nonCacheable() {
        $this->_nonCacheable = true;
        return $this;
    }


    /**
     * 设置pdo展示数据的方式
     *        有一个隐藏值:自设 STATEMENT[ setFetchMode('STATEMENT') ]
     *        可以直接返回pdoStatement对象，只有在READ的时候才有效
     *        其他设置如PDO::FETCH_OBJ 对象访问，本类默认
     *        PDO::FETCH_ASSOC，字段名和值关联数组，PDO::FETCH_NUM 数字索引数组
     * @param int $fetchMode
     * @return $this
     */
    public function mode($fetchMode = PDO::FETCH_OBJ) {
        $this->_fetchMode = $fetchMode;
        return $this;
    }

    public function useSqlStore() {
        $this->_useSqlStore = true;
        return $this;
    }

    public function getSqlStore() {
        return $this->_sqlStore;
    }

    /**
     * SELECT可加入到二级缓存的pdo访问方式，为推荐访问方式 *****
     * 如果select语句中未限定limit应确保没有超大数据集
     * 否则应设置 setFetchMode('STATEMENT')直接返回pdo对象进行操作
     * 始终返回对象访问方式。
     * @param $sql
     * @param null $bindParam
     * @param string $operation 操作类型 当为READ或者包含READ时返回结果集，其他操作会同步缓存
     * @return null|type
     */
    public function prepareBindLevel2Cache($sql, $bindParam = null, $operation = 'READ') {
        $this->_sqlParamStorage = null;
        //无法使用缓存或原子操作时不使用缓存都直接返回prepare()方法
        if (!$this->_canUseLevel2cache || $this->_nonCacheable) {
            $this->_nonCacheable = false; //重置为使用
            return $this->prepare($sql, $bindParam, $operation);
        }

        $_cacheable = false;
        if ($this->_cacheable) {
            $this->_cacheable = false;
            $_cacheable = true;
        }

        $_resultObj = null;
        $_isRead = strpos($operation, 'READ') !== false ? true : false; //select必须出现在首位
        if ($_cacheable || !$_isRead) {
            $sql = $this->_setPrefix($sql); //注：这里为缓存准备数据多运行一次
            $_runtimeId = $this->_cachePack . '~runtime~';
            $_match = null;
            $_flushCacheHashList = false;
            preg_match_all('/(' . $this->_dsn['prefix'] . '[^`\s]+)/i', $sql, $_match);
            /**
             * 如果缓存这条查询(isRead)则初始化参数
             */
            if ($_isRead) {
                $_m = false; //是否在mapping中命中缓存
                $_hashSql = $this->_outSqlBuffer($sql, $bindParam); //便于调试
                $_hashId = md5($_hashSql); //本次缓存的id
                //hash平衡型缓存，以mapping数组数字索引为条件
                $_runtimeHashIndex = dechex(0);
            }
            //映射为空或者不存在，索引不超时
            if (!($_mapping = $this->_cache->get($_runtimeId))) {
                $_mapping = array($_match[0]);
                $this->_cache->set($_runtimeId, $_mapping); //立即缓存，避免高并发情况脏读，非线程安全
                if ($_isRead) {
                    $_aryHashList = array($_hashId => $_runtimeHashIndex);
                    $this->_cache->set($_runtimeId . $_runtimeHashIndex, $_aryHashList);
                }
            } else {
                if (count($_match[0]) > 0) {
                    if ($_isRead) {
                        $_aryHashList = array();
                        //TODO:如果pdo下缓存文件过多，可以进一步将缓存文件hash散布
                        if (false !== ($_m = array_search($_match[0], $_mapping))) {//命中
                            $_runtimeHashIndex = dechex($_m);
                            //取出runtime中保存的hash列表，hash列表不超时
                            if (($_aryHashList = $this->_cache->get($_runtimeId
                                    . $_runtimeHashIndex)) && count($_aryHashList) > 0) {
                                //命中直接返回结果
                                //false只是返回文件不存在，这里值为空也可以返回
                                //缓存存在
                                if (isset($_aryHashList[$_hashId]) &&
                                    false !== ($_resultObj = $this->_cache
                                        ->expires($this->_expires)
                                        ->get($this->_cachePack . $_runtimeHashIndex . '/' . $_hashId))) {
                                    $_cacheable = false;
                                    return $_resultObj; //命中
                                }
                                //但缓存方式为磁盘的时候，同时有超时限制，则启动过期文件清扫(主动清扫)
                                //推荐以memcache方式提高命中率及回收效率(memcache自动管理过期，被动)
                                if ($this->_expires > 0 &&
                                    strcasecmp('file', $this->_dsn['cacheType']) == 0) {
                                    foreach ($_aryHashList as $_hl => $_discardable) {
                                        if (false === $this->_cache->expires(
                                                $this->_expires)->get(
                                                $this->_cachePack
                                                . $_runtimeHashIndex . '/' . $_hl)) {
                                            unset($_aryHashList[$_hl]);
                                            $this->_cache->delete($this->_cachePack
                                                . $_runtimeHashIndex . '/' . $_hl);
                                        }
                                    }
                                }
                            }
                        } else {//未命中立即缓存
                            array_push($_mapping, $_match[0]);
                            $this->_cache->set($_runtimeId, $_mapping);
                            end($_mapping);
                            $_runtimeHashIndex = key($_mapping);
                        }
                        $_aryHashList[$_hashId] = $_runtimeHashIndex;
                        $_flushCacheHashList = true;
                    } else {//只取第一张表，一般情况下只更新一张表
                        //logger::debug($sql . ' = ' . $_hashId);
                        foreach ($_mapping as $_rId => $_mp) {
                            //第一张表存在于mapping key中，则读取hash列表并删除索引的hash值
                            if (in_array($_match[0][0], $_mp) &&
                                false !== ($_aryHashList = $this->_cache->get($_runtimeId . $_rId)) &&
                                count($_aryHashList) > 0) {
                                foreach ($_aryHashList as $_hl => $_discardable) {
                                    $this->_cache->delete($this->_cachePack
                                        . $_rId . '/' . $_hl);
                                }
                            }
                        }
                    }
                }
            }
        }
        //如果缓存未命中或者非查询
        $_resultObj = $this->prepare($sql, $bindParam, $operation);

        if ($_cacheable || !$_isRead) {
            if ($_isRead && count($_resultObj) > $this->_level2TopLimit) {
                //存储真实数据，未从缓存中返回，始终将结果集加入缓存
                //可设置缓存时间
                if ($_flushCacheHashList) {
                    $this->_cache->set($_runtimeId . $_runtimeHashIndex, $_aryHashList);
                    $_flushCacheHashList = false;
                }
                $this->_cache->expires($this->_expires)->set($this->_cachePack
                    . $_runtimeHashIndex . '/' . $_hashId, $_resultObj);
            }
            $_mapping = null;
            unset($_mapping);
        }

        return $_resultObj;
    }

    /**
     * 绑定参数访问数据库，安全的
     * 推荐，但不绑定二级缓存，返回对象为pdo statement可作用pdo对象
     * @param type $sql
     * @param type $bindParam
     * @return type
     */
    public function prepare($sql, $bindParam = null, $operation = 'READ') {
        $this->_lazyConn();
        $_isProcedure = strpos($sql, 'CALL') !== false ? true : false;
        $sql = $this->_setPrefix($sql);
        if (($operation == 'INSERT' || $operation == 'UPDATE' || $_isProcedure) &&
            !is_null($bindParam)) {
            $bindParam = $this->_fixedBindParam($bindParam);
        }

        if (strcasecmp('INSERT', $operation) != 0) {//INSERT 需要额外处理
            $bindParam = $this->_autoFillBindParam($sql, $bindParam);
        }

        $sql = $this->_compatible($sql);

        if ($this->_debug) {
            //die($sql);
            logger::debug($this->_outSqlBuffer($sql, $bindParam));
            $this->_debug = false;
        }

        if ($this->_useSqlStore && strpos($operation, 'READ') === false) {
            $this->_sqlStore[] = $this->_outSqlBuffer($sql, $bindParam);
        }

        $this->_pdoStatement = $this->_pdo->prepare($sql);
//		logger::debug($sql);
//		logger::debug($bindParam);

        if ($_isProcedure) {
            $_paramList = array();
            $_outParam = array();
            if (!is_null($bindParam) && count($bindParam) > 0) {
                $_i = 1;
                foreach ($bindParam as $_k => $_v) {
                    $_paramList[$_k] = $_v[0];
                    if (abs($_v[1]) > 100) {//input_output
                        $_outParam[$_k] = null;
                    }

                    if (count($_v) > 2) {
                        $this->_pdoStatement->bindParam($_i, $_paramList[$_k], $_v[1], $_v[2]);
                    } else {
                        $this->_pdoStatement->bindParam($_i, $_paramList[$_k], $_v[1]);
                    }
                    $_i++;
                }
            }
            $this->_pdoStatement->execute();
        } else {
            $this->_pdoStatement->execute($bindParam);
        }

        ++$this->_total;

        $_resultObj = null;
        $_tmp = null;
        switch ($operation) {
            case 'READ' :
                $_resultObj = strcmp('STATEMENT', $this->_fetchMode) == 0 ?
                    $this->_pdoStatement :
                    $this->_pdoStatement->fetchAll($this->_fetchMode);
                $this->_fetchMode = PDO::FETCH_OBJ; //每次用完重置为object方式
                break;
            case 'READ_ONE' :
                $_tmp = $this->_pdoStatement->fetchAll($this->_fetchMode);
                if (count($_tmp) > 0)
                    $_resultObj = $_tmp[0];
                $this->_fetchMode = PDO::FETCH_OBJ;
                break;
            case 'READ_COLUMN' :
                $_tmp = $this->_pdoStatement->fetchAll(PDO::FETCH_COLUMN);
                if (count($_tmp) > 0)
                    $_resultObj = $_tmp[0];
                break;
        }
        unset($_tmp);
        $_tmp = null;

        if ($_isProcedure) {//stored procedure
            if (count($_outParam) > 0) {
                foreach ($_outParam as $_k => $_v) {
                    $_outParam[$_k] = $_paramList[$_k];
                }
            }
            return array('result' => $_resultObj, 'out' => $_outParam);
        }

        return $_resultObj;
    }

    private function _pdoType($_val) {
        if (is_bool($_val)) {
            return PDO::PARAM_BOOL;
        } else if (is_int($_val)) {
            return PDO::PARAM_INT;
        } else if (is_null($_val)) {
            return PDO::PARAM_NULL;
        } else {
            return PDO::PARAM_STR;
        }
    }

    /**
     * 获取记录集 SELECT 如果有参数带入，不要使用本方法，避免sql注入
     * sql 语句需要处理敏感字符，非安全的，不推荐
     * 不推荐使用，不绑定二级缓存
     * @param $sql
     * @return null
     */
    public function query($sql) {
        $sql = $this->_setPrefix($sql);
        if ($this->_debug) {
            logger::debug($sql);
        }
        $this->_lazyConn();

        if ($this->_useSqlStore) {
            $this->_sqlStore[] = $sql;
        }

        $this->_pdoStatement = $this->_pdo->query($sql);
        //关闭游标，防止多个sql访问抛出 execute 不存在 php编译时使用mysqlnd驱动无此问题
        //$this->_pdoStatement->closeCursor();
        ++$this->_total;

        return $this->_pdoStatement;
    }

    /**
     * 发送SQL 查询，并不获取和缓存结果的行 INSERT UPDATE DELETE
     * sql 语句需要处理敏感字符
     * 不推荐使用，不绑定二级缓存
     * @param $sql
     * @return null
     */
    public function exec($sql) {
        $sql = $this->_setPrefix($sql);
        if ($this->_debug) {
            logger::debug($sql);
        }
        ++$this->_total;

        $this->_lazyConn();

        if ($this->_useSqlStore) {
            $this->_sqlStore[] = $sql;
        }

        $this->_pdoStatement = $this->_pdo->exec($sql);

        return $this->_pdoStatement;
    }

    /**
     * all one field excute
     * @param $proc
     * @param string $operation
     * @return $this
     */
    public function procedure($proc, $operation = 'excute') {
        if (!isset($this->_sqlParamStorage['procedure'])) {
            $this->_sqlParamStorage['procedure'] = $proc;
            $this->_sqlParamStorage['operation'] = $operation;
        }
        return $this;
    }

    public function getPrefix() {
        return $this->_dsn['prefix'];
    }

    /**
     * 更改成指定的数据库表前缀
     * @param $sql
     * @return mixed
     */
    private function _setPrefix($sql) {
        return str_replace($this->_prefix, $this->_dsn['prefix'], $sql);
    }

    /**
     *
     * @return boolean
     */
    public function call() {
        if (count($this->_sqlParamStorage) > 0) {
            $this->_operation = 'EXCUTE';
            switch (strtolower($this->_sqlParamStorage['operation'])) {
                case 'excute' :
                    break;
                case 'all' :
                    $this->_operation = 'READ';
                    break;
                case 'one' :
                    $this->_operation = 'READ_ONE';
                    break;
                case 'field' :
                    $this->_operation = 'READ_COLUMN';
                    break;
            }
            return $this->prepareBindLevel2Cache('CALL ' . $this->_sqlParamStorage['procedure'],
                isset($this->_sqlParamStorage['bind']) ? $this->_sqlParamStorage['bind'] : null, $this->_operation);
        }
        return false;
    }

    /**
     * 运行安全的预绑定sql插入，返回最后一个插入的id
     * @param <type> $sql
     * @param <type> $bindParam
     * @return <type>
     */
    public function sqlInsert($sql, $bindParam = null) {
        $this->prepareBindLevel2Cache($sql, $bindParam, 'INSERT');
        return $this->lastInsertId();
    }

    /**
     * 运行安全的预绑定sql查询，返回影响的行数
     * @param <type> $sql
     * @param <type> $bindParam
     * @return <type>
     */
    public function sqlExecute($sql, $bindParam = null) {
        $this->prepareBindLevel2Cache($sql, $bindParam, 'UPDATE');
        return $this->_pdoStatement->rowCount();
    }

    /**
     * 安全的预绑定数据库sql单记录阅读器，返回一条记录
     * @param $sql
     * @param $bindParam
     * @return null|type 一条记录，object
     */
    public function sqlDataReadOne($sql, $bindParam) {
        return $this->prepareBindLevel2Cache($sql, $bindParam, 'READ_ONE');
    }

    /**
     * 安全的预绑定数据库sql字段阅读器，返回运行结果的一个字段
     * @param $sql
     * @param null $bindParam
     * @return null|type 运行结果的一个字段，field
     */
    public function sqlDataReadField($sql, $bindParam = null) {
        return $this->prepareBindLevel2Cache($sql, $bindParam, 'READ_COLUMN');
    }

    /**
     * 事务开始
     * @return type
     */
    public function beginTransaction() {
        $this->_lazyConn();
        return $this->_pdo->beginTransaction();
    }

    /**
     * 事务提交
     * @return type
     */
    public function commit() {
        return $this->_pdo->commit();
    }

    /**
     * 事务回滚
     * @return type
     */
    public function rollBack() {
        return $this->_pdo->rollBack();
    }

    /**
     * 拼接sql及param
     * @param type $sql
     * @param type $bindParam
     * @return type
     */
    private function _outSqlBuffer($sql, $bindParam = null) {
        if (!is_null($bindParam)) {
            $_count = count($bindParam);
            for ($_i = 0; $_i < $_count; $_i++) {
                $sql = preg_replace('/\?|(:\S*)/', '===' . $_i . '===', $sql, 1);
            }
            $_i = 0;
            foreach ($bindParam as $_v) {
                if (is_array($_v)) {
                    $_v = "'{$_v[0]}'";
                } else if (!is_numeric($_v)) {
                    $_v = "'{$_v}'";
                }
                $sql = str_replace("==={$_i}===", $_v, $sql);
                $_i++;
            }
        }
        return $sql;
    }

    /**
     * 自动填充参数
     * @param <type> $parseSql
     * @param <type> $bindParam
     * @return type
     */
    protected function _autoFillBindParam($parseSql, $bindParam) {
        $_bindCount = count($bindParam);
        if (is_null($bindParam) || $_bindCount == 0)
            return null;

        $_autoFill = false;
        if (key($bindParam) !== 0) {
            end($bindParam);
            if (key($bindParam) !== ($_bindCount - 1)) { //autoFill 自动填充
                $_bindParam = array();
                preg_match_all('/\s?`?(\w+)`?\s+[\+-=><]+\s+\?/iU', $parseSql, $_match);
//                logger::debug($_match[1]);
                if (count($_match[1]) > 0) {
                    foreach ($_match[1] as $_k) {
                        if (isset($bindParam[$_k])) {
                            $_bindParam[] = $bindParam[$_k];
                        } else {
                            $_bindParam[] = '';
                        }
                    }
                    return $_bindParam;
                }
            }
            reset($bindParam);
        }
        return $bindParam;
    }

    /**
     * 数据库连接延迟加载，在缓存存在的情况下并不总是创建数据库连接
     */
    private function _lazyConn() {
        if (is_null($this->_pdo)) {
            try {
                $_PDOAttr = array(
                    PDO::ERRMODE_EXCEPTION => true
                );
                //使用一个存在的连接，长连接
                if (isset($this->_dsn['persistent'])) {
                    $_PDOAttr[PDO::ATTR_PERSISTENT] = $this->_dsn['persistent'];
                    $this->_persistent = true;
                }
                $_charset = '';
                $_vlt = version_compare(PHP_VERSION, '5.3.7', 'lt');
                $_setNames = "SET NAMES '{$this->_dsn['charset']}'";
                if ($_vlt) {
                    //不使用PDO的预处理，使用数据库的预处理
                    //PHP 5.3.6 之前关闭pdo预处理同时DSN中charset无用
                    //5.3.6+ 版本DSN设置了charset则pdo预处理关闭与否都是安全的
                    if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                        $_PDOAttr[PDO::MYSQL_ATTR_INIT_COMMAND] = $_setNames;
                    }
                    $_PDOAttr[PDO::ATTR_EMULATE_PREPARES] = false; //pdo预处理关闭
                } else {
                    $_charset = "charset={$this->_dsn['charset']};";//本地驱动转义时使用指定的字符集
                }

                $_dsn = "{$this->_dsn['driver']}:host={$this->_dsn['host']};"
                    . "dbname={$this->_dsn['dbName']};"
                    . $_charset
                    . "port={$this->_dsn['port']}";
                if (strcasecmp('oci', $this->_dsn['driver']) == 0) {
                    $_dsn = "oci:dbname=//{$this->_dsn['host']}:{$this->_dsn['port']}"
                        . "/{$this->_dsn['dbName']};"
                        . "charset={$this->_dsn['charset']}";
                    $_PDOAttr[PDO::ATTR_CASE] = PDO::CASE_LOWER; //oracle返回字段保持小写
                }

                $this->_pdo = new PDO($_dsn, $this->_dsn['user'], $this->_dsn['password'],
                    $_PDOAttr
                );
                if ($_vlt && !defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                    $this->_pdo->exec($_setNames);
                }
            } catch (PDOException $e) {
                throw new FormatException('can not connect database.please check the config file.', $e);
            }
        }
    }

    /**
     * 手动关闭数据库，同时注销instance，目的用于切换数据源
     * 否则不需要手动关闭
     */
    abstract function close();

    public function __destruct() {
        $this->_pdoStatement = null;
        $this->_pdo = null;
        $this->_dsn = null;
    }

}
