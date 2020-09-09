<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class InvalidParameterException extends Exception
{
    public function getServerErrorCode()
    {
        return 400;
    }

    public function __construct($parameter = "", $value = "", $code = 0, \Exception $previous = null)
    {
        $message = sprintf("Invalid value for %s parameter: %s", $parameter, $value);
        parent::__construct($message, $code, $previous);
    }
}