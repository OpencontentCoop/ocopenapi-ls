<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class TooManyRequestsException extends Exception
{
    public function getServerErrorCode()
    {
        return 429;
    }

    public function __construct($waitFor, \Exception $previous = null)
    {
        $message = sprintf("Too many request: wait for %d seconds", $waitFor);
        parent::__construct($message, 0, $previous);
    }
}