<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class BootstrapItaliaIconFactoryProvider  extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = array(
            "type" => "string",
            "enum" => array_values(\OpenPABootstrapItaliaIconType::getIconList()),
            "description" => $this->getPropertyDescription(),
        );

        return $schema;
    }
}