<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class DateTimeFactoryProvider extends DateFactoryProvider
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $data['nullable'] = true;
        $data['format'] = 'data-time';

        return $data;
    }

    public function serializeValue(Content $content, $locale)
    {
        return $this->getContent($content, $locale);
    }

}