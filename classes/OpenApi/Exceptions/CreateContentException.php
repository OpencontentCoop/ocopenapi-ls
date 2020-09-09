<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class CreateContentException extends Exception
{
    public function getServerErrorCode()
    {
        return 400;
    }

}