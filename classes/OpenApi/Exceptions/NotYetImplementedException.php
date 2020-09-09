<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class NotYetImplementedException extends Exception
{
    public function getServerErrorCode()
    {
        return 501;
    }

    public function __construct($method = "", $path = "", $phpClass = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Method %s for %s endpoint: not yet implemented (%s)", $method, $path, $phpClass);
        parent::__construct($message, $code, $previous);
    }
}