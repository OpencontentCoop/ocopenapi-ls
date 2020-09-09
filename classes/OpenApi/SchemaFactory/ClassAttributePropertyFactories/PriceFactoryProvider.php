<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class PriceFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = parent::provideProperties();
        $schema['type'] = 'number';
        $schema['format'] = 'float';

        return $schema;
    }

}