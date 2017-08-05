<?php

namespace Phoenix\Exception;

if (!defined('IN_PX'))
    exit;

use Exception;
use Phoenix\Support\Helpers;
use Phoenix\Log\Log4p as logger;

/**
 * Class RPCException
 * @package Phoenix
 */
class RPCException extends FormatException {

    public function formatException() {
        $_trace = $_file = '.';
        if (PX_DEBUG) {
            $_trace = nl2br($this->_self->getTraceAsString());
            $_file = "{$this->_self->file} (Line:{$this->_self->line})";
        }
        $_logger = <<<EOF
Exception Trace:
Code: {$this->_message}
Message: {$this->_self->getMessage()}
File: {$_file}
Trace: {$_trace}
EOF;
        logger::error($_logger);
        return Helpers::jsonEncode(array(
            'requestId' => -1,
            'error' => -1,
            'result' => $this->_self->getMessage()
        ));
    }

    public static function staticException(Exception $e) {
        if (!method_exists($e, 'formatException')) {
            $e = new self('', $e);
        }
        echo $e->formatException();
        exit;
    }

}
