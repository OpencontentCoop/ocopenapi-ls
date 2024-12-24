<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class DateTimeFactoryProvider extends DateFactoryProvider
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $data['nullable'] = true;
        $data['format'] = 'data-time';

        return $data;
    }

}