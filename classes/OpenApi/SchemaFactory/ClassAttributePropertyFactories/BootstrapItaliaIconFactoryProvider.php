<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

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

    public function serializeValue(Content $content, $locale)
    {
        return (string)$this->getContent($content, $locale);
    }
}