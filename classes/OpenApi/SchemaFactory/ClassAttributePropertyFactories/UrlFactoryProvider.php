<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class UrlFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return array(
            "type" => "string",
            "nullable" => true,
            "format" => "uri",
            "description" => $this->getPropertyDescription(),
        );
    }

}