<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class OutOfRangeException extends Exception
{
    public function getServerErrorCode()
    {
        return 500;
    }

    public function __construct($current = "", $range = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Current value %s is out of %s range", $current, $range);
        parent::__construct($message, $code, $previous);
    }
}