<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class UserFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return array(
            "type" => "object",
            "description" => $this->getPropertyDescription(),
            "properties" => array(
                "login" => array(
                    "title" => \ezpI18n::tr( 'design/standard/content/datatype', 'Username' ),
                    "type" => "string"
                ),
                "email" => array(
                    "title" => \ezpI18n::tr( 'design/standard/content/datatype', 'Email' ),
                    "format" => "email"
                )
            ),
        );
    }

}