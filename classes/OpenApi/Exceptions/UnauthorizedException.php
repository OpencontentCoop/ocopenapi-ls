<?php

namespace Opencontent\OpenApi\Exceptions;

// Force 403 ezprest response
use Opencontent\OpenApi\Exception;

class UnauthorizedException extends Exception
{
    public function getServerErrorCode()
    {
        return 401;
    }
}