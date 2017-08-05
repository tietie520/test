<?php

namespace Phoenix\Exception;

if (!defined('IN_PX'))
    exit;

use Exception;

/**
 * Class FormatException
 * @package Phoenix
 */
class FormatException extends Exception implements PXException {

    protected $_self;
    protected $_message;

    public function __construct($message, $exception = 0) {
        if (is_object($exception)) {
            $_code = $exception->getCode();
            $this->_message = "[{$exception->getCode()}]" . ' ' . $message;

            $this->_self = $exception;
        } else {
            $_code = $exception;
            $this->_message = $_code;

            $this->_self = $this;
        }
        parent::__construct($message, $_code);
    }

    public function formatException() {
        $_trace = $_file = '.';
        if (PX_DEBUG) {
            $_trace = nl2br($this->_self->getTraceAsString());
            $_file = "{$this->_self->file} (Line:{$this->_self->line})";
        }
        $_return = <<<EOF
<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<title>Exception Trace</title>
<style>
html, body { height:100%; margin:0; padding:0; color:#7A6B48; font:12px/1.5 "微软雅黑", arial, tahoma, simsun, sans-serif; background-color:#eee }
.red { color:#d00 }
table { border-collapse:collapse; width:100%; height:100%; margin:-1px 0 0 -1px }
table th { text-align:right; width:80px }
table th, table td { padding:5px; vertical-align:top; border-left:1px solid white; border-top:1px solid white }
.h18 { height:18px; }
</style>
</head>
<body>
    <table>
        <tr>
            <th class="h18">Code:</th>
            <td>{$this->_message}</td>
        </tr>
        <tr>
            <th class="h18">Message:</th>
            <td class="red">{$this->_self->getMessage()}</td>
        </tr>
        <tr>
            <th class="h18">File:</th>
            <td class="red">{$_file}</td>
        </tr>
        <tr>
            <th>Trace:</th>
            <td>{$_trace}</td>
        </tr>
    </table>
</body>
</html>
EOF;
        return $_return;
    }

    public static function staticException(Exception $e) {
        if (!method_exists($e, 'formatException')) {
            $e = new self('', $e);
        }
        echo $e->formatException();
        exit;
    }

}
