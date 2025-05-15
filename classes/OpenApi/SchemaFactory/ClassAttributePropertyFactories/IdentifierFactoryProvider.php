<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class IdentifierFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function serializeValue(Content $content, $locale)
    {
        return (string)$this->getContent($content, $locale);
    }
}