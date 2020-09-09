<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class BooleanFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $data['type'] = "boolean";

        return $data;
    }

    public function serializeValue(Content $content, $locale)
    {
        return (bool)$this->getContent($content, $locale);
    }
}