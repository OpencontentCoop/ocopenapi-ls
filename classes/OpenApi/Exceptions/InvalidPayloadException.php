<?php


namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class InvalidPayloadException extends Exception
{
    public function getServerErrorCode()
    {
        return 400;
    }

    public function __construct($message = "", $value = "", $code = 0, \Exception $previous = null)
    {
        if (!empty($value) && is_string($value)) {
            $message = sprintf("Invalid value for %s: %s", $message, $value);
        }
        parent::__construct($message, $code, $previous);
    }
}