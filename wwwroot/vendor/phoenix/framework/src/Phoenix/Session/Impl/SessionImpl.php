<?php

namespace Phoenix\Session\Impl;

if (!defined('IN_PX'))
    exit;

use Phoenix\Session\Session;
use Phoenix\Session\Store;
use App\Tools\Auxi;
use Phoenix\Log\Log4p as logger;

/**
 * UI.Session 所有Session访问的入口
 *
 */
class SessionImpl implements Session {

    //持久层
    private function __Repository() {}

    //cfg来自框架 $dsn未定义是回去route中取得定义，否则抛出异常
    private function __Value($__ROOT__, $__DOMAIN__, $dsn) {}

    private function __Inject($db) {}

    const SESSION_NAME = 'PXAPP';

    private $_sessionCreateTimeout = 28800; //连续活动8个小时需要重新登录 秒级
    private $_sessionTimeout = 1800; // 15 * 60 30分钟超时 秒级
    /**
     * 会话回收概率
     * 默认1/50的概率进行回收
     * 如果不活动的用户占用内存过多，可以减少数值，加大回收几率
     * 注意：这个值不能为0
     * @var int
     */
    private $_gcProbability = 50;
    private $_sessionHandler = null;
    private $_realSessionId = null;
    private $_salt = null; //加入salt，避免在memcache中不同项目key一致
    private $_cookiePath;
    private $_domain;

    /**
     * 存储的会话数据
     * @var array
     */
    private $_data = array();

    public function __construct(& $__ROOT__, & $__DOMAIN__, & $dsn, $db) {
        if (is_null($this->_sessionHandler)) {
            switch (strtolower($dsn['default']['sessionType'])) {
                case 'database':
                    $this->_run(new DatabaseStoreImpl($db));
                    break;
                case 'memcache':
                    $this->_run(new MemcacheStoreImpl($dsn['default']['memServers']));
                    break;
                case 'memcached':
                    $this->_run(new MemcachedStoreImpl($dsn['default']['memServers'],
                        $this->dsn['default']['dbName'], $dsn['default']['sessionType']));
                    break;
            }
            //salt不能为空，否则memcache模式下会有安全问题
            $this->_salt = & $dsn['default']['dbName'];
            $this->_cookiePath = & $__ROOT__;
            $this->_domain = & $__DOMAIN__;
            $this->_sessionHandler->open($this->_sessionCreateTimeout, $this->_sessionTimeout);
        }
        if (isset($_COOKIE[self::SESSION_NAME])) { //得到钩子
            $this->_realSessionId = $this->_getRealSessionId($_COOKIE[self::SESSION_NAME]); //得到真实id
        } else if (isset($_REQUEST[self::SESSION_NAME])) {
            $this->_realSessionId = $this->_getRealSessionId($_REQUEST[self::SESSION_NAME]); //得到真实id
        } else if (isset($_REQUEST['token'])) {
            $this->_realSessionId = $this->_getRealSessionId($_REQUEST['token']); //得到真实id
        }
        if (!is_null($this->_realSessionId) &&
            $this->_sessionHandler->activity($this->_realSessionId)) {//如果设置活动成功，获取数据
            $this->_data = $this->_sessionHandler->get($this->_realSessionId);
        } else {
            $this->_realSessionId = null;
        }
    }

    /**
     * 赋值完成后提交session
     */
    public function set() {
        if (is_null($this->_realSessionId)) {
            $_token = '';
            if (isset($_COOKIE[self::SESSION_NAME])) {
                $_token = $_COOKIE[self::SESSION_NAME];
            } else if (isset($_REQUEST[self::SESSION_NAME])) {
                $_token = $_REQUEST[self::SESSION_NAME];
            } else {
                $_token = md5(Auxi::guid()); //返回cookie的token只是钩子
                //http only
                setcookie(self::SESSION_NAME, $_token, 0, $this->_cookiePath, $this->_domain, false, true);
            }
            $this->_realSessionId = $this->_getRealSessionId($_token);

            $this->_data['token'] = $_token;

        }
        $this->_sessionHandler->set($this->_realSessionId, $this->_data);
    }

    /**
     * 更新session中值 参数必须为键值对
     */
    public function update($name, $aryUp = array()) {
        if (count($aryUp) > 0) {
            foreach ($aryUp as $_key => $_value) {
                $this->_data[$name][$_key] = $_value;
            }
            $this->set();
        }
    }

    /**
     * 注销凭据
     * @param $key
     */
    public function destory($key) {
        if (!is_null($this->_realSessionId)) {
            if (count($this->_data) > 1) {
                unset($this->_data[$key]);
                $this->_sessionHandler->set($this->_realSessionId, $this->_data);
            } else {
                $this->_data = array(); //清空数据
                $this->_sessionHandler->destory($this->_realSessionId);
                setcookie(self::SESSION_NAME, '', -1, $this->_cookiePath, $this->_domain);
                $this->_realSessionId = null;
            }
        }
    }

    /**
     * session销毁
     * 注意：因为page中存在调用，这个调用会在Page的销毁之后执行
     * 所以这个析构不要添加与页面交互的代码
     */
    public function __destruct() {
        //$this->_gcProbability = (int) (get_cfg_var('session.gc_divisor') / get_cfg_var('session.gc_probability'));
        if (mt_rand(1, $this->_gcProbability) == 1) {
            $this->_sessionHandler->gc();
        }
    }

    /**
     * 用户换了ip需要重新登录
     * 若能获取客户端mac地址，将会更安全
     * @param null $token
     * @return string
     */
    private function _getRealSessionId($token = null) {
        $_userIp = Auxi::getIP();
        return md5($_userIp . (is_null($token) ? Auxi::guid() : $token) . $this->_salt);
    }

    private function _run(Store & $handler) {
        $this->_sessionHandler = $handler;
    }

    public function __get($name) {
        if (isset($this->_data[$name])) {
            return $this->_data[$name];
        }
        return null;
    }

    public function __set($name, $value) {
        if (isset($this->_data[$name])) {
            if (count($value) > 0) {
                foreach ($value as $_key => $_value) {
                    if (is_null($_value)) {
                        $this->_data[$name][$_key] = null;
                        unset($this->_data[$name][$_key]);
                    } else {
                        $this->_data[$name][$_key] = $_value;
                    }
                }
            }
        } else {
            $this->_data[$name] = $value;
        }
        $this->set();
    }

}
