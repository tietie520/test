<?php

namespace Phoenix\Exception;

use Exception;

interface PXException {

    public function formatException();

    public static function staticException(Exception $e);

}