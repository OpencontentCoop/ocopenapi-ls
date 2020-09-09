<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class NotFoundException extends Exception
{
    public function getServerErrorCode()
    {
        return 404;
    }

    public function __construct($identifier = "", \Exception $previous = null)
    {
        $message = sprintf("Content %s not found", $identifier);
        parent::__construct($message, 0, $previous);
    }

}