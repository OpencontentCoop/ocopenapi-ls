<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class KeywordsFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $data['nullable'] = true;

        return $data;
    }

}