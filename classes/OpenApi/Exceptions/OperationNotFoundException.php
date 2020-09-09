<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class OperationNotFoundException extends Exception
{
    public function getServerErrorCode()
    {
        return 404;
    }
}