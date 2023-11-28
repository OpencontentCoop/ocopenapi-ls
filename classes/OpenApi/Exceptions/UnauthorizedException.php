<?php

namespace Opencontent\OpenApi\Exceptions;

// Force 403 ezprest response
class UnauthorizedException extends \ezpContentAccessDeniedException
{
    public function __construct($message)
    {
        $this->message = $message;
    }

}