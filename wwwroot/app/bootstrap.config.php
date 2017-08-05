<?php

error_reporting(E_ALL);
define('IN_PX', true);
define('PX_DEBUG', true); //是否打开开发模式
define('PX_DEBUG_DISPLAY', false); //是否显示开发模式调试信息，开发模式关闭时此项无效
define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR); //5.3+
define('APP_PATH', __DIR__ . DIRECTORY_SEPARATOR);
define('CACHE_PATH', ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR);
define('TMP_PATH', ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR);
define('LOG_PATH', ROOT_PATH . 'data' . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR);

//设置时区：中华人民共和国
if (version_compare(PHP_VERSION, '5.1.0', 'gt')) {
    date_default_timezone_set('PRC');
}

require ROOT_PATH . 'vendor/autoload.php';
