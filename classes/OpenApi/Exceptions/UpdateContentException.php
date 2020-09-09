<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class UpdateContentException extends Exception
{
    public function getServerErrorCode()
    {
        return 400;
    }

}