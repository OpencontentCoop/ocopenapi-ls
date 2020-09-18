<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class BootstrapItaliaIconFactoryProvider  extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = array(
            "type" => "string",
            "enum" => array_column(array_values(\OpenPABootstrapItaliaIconType::getIconList()), 'value'),
            "description" => $this->getPropertyDescription(),
        );

        return $schema;
    }
}