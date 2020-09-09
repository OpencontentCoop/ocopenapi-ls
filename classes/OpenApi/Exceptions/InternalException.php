<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class InternalException extends Exception
{
    public function getServerErrorCode()
    {
        return 500;
    }
}