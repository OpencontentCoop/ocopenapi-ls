<?php

namespace Opencontent\OpenApi\Exceptions;

use Opencontent\OpenApi\Exception;

class TranslationNotFoundException extends Exception
{
    public function getServerErrorCode()
    {
        return 404;
    }

    public function __construct($identifier = "", $locale = "", \Exception $previous = null)
    {
        $message = sprintf("Content %s not found in %s language", $identifier, $locale);
        parent::__construct($message, 0, $previous);
    }

}