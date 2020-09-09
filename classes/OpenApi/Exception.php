<?php

namespace Opencontent\OpenApi;

use Opencontent\Opendata\Api\Exception\BaseException;

class Exception extends BaseException
{
    public function __construct($message = "", $code = 0, \Exception $previous = null)
    {
        if (empty($message)){
            $message = $this->getDefaultMessage();
        }
        parent::__construct($message, $code, $previous);
    }

    public function getDefaultMessage()
    {
        $parts = explode('.', $this->getErrorType());
        $name = str_replace('Exception', '', array_pop($parts));

        return ucfirst(StringTools::fromCamelCase($name, ' '));
    }
}