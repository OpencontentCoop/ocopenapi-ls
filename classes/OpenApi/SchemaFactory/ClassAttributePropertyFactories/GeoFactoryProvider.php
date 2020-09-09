<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class GeoFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return [
            "type" => "object",
            "description" => $this->getPropertyDescription(),
            "properties" => array(
                "address" => array(
                    "title" => 'Address',
                    "type" => "string"
                ),
                "longitude" => array(
                    "title" => "Longitude",
                    "type" => "number",
                    "format" => "float"
                ),
                "latitude" => array(
                    "title" => "Latitude",
                    "type" => "number",
                    "format" => "float"
                )
            )
        ];
    }
}