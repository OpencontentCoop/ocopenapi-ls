<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class AuthorFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return array(
            "type" => "array",
            "description" => $this->getPropertyDescription(),
            "items" => array(
                "type" => "object",
                "properties" => array(
                    "name" => array(
                        "title" => \ezpI18n::tr( 'design/standard/content/datatype', 'Name' ),
                        "type" => "string"
                    ),
                    "email" => array(
                        "title" => \ezpI18n::tr( 'design/standard/content/datatype', 'Email' ),
                        "format" => "email"
                    )
                )
            ),
            'minItems' => (bool)$this->attribute->attribute('is_required') ? 1 : 0
        );
    }

    public function serializeValue(Content $content, $locale)
    {
        return (array)$this->getContent($content, $locale);
    }
}