<?php

namespace Phoenix\Cache;

if (!defined('IN_PX'))
    exit;

use Memcached;
use Phoenix\Exception\FormatException;

/**
 * memcached
 */
class MemcachedHandler {

    static $_instances = null;

    public static function create($hosts, $salt, $mode) {
        if (is_null(static::$_instances)) {
            static::$_instances = new Memcached($salt);
            if (count(static::$_instances->getServerList()) == 0) {
//            static::$_instances->setOption(Memcached::OPT_RECV_TIMEOUT, 1000);
//            static::$_instances->setOption(Memcached::OPT_SEND_TIMEOUT, 1000);
//            static::$_instances->setOption(Memcached::OPT_TCP_NODELAY, true);
//            static::$_instances->setOption(Memcached::OPT_SERVER_FAILURE_LIMIT, 50);
//            static::$_instances->setOption(Memcached::OPT_CONNECT_TIMEOUT, 500);
//            static::$_instances->setOption(Memcached::OPT_RETRY_TIMEOUT, 300);
//            static::$_instances->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
//            static::$_instances->setOption(Memcached::OPT_REMOVE_FAILED_SERVERS, true);
//            static::$_instances->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
                static::$_instances->setOption(Memcached::OPT_BINARY_PROTOCOL, true); //使用binary二进制协议
                static::$_instances->setOption(Memcached::OPT_COMPRESSION, false); //关闭压缩功能
                switch ($mode) {
                    case 'aliocs' :
                        //添加OCS实例地址及端口号
                        static::$_instances->addServer($hosts['aliocs'][0], $hosts['aliocs'][1]);
                        //设置OCS帐号密码进行鉴权
                        if (count($hosts['aliocs']) > 2) {
                            static::$_instances->setSaslAuthData($hosts['aliocs'][2], $hosts['aliocs'][3]);
                        }
                        break;
                    case 'memcached' :
                        static::$_instances->addServers($hosts['memcached']);
                        break;
                    default :
                        throw new FormatException(MsgHelper::get('0x00004001'),
                            '0x00004001');
                        break;
                }
            }
        }
        return static::$_instances;
    }

}
