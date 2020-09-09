<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class StringFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = parent::provideProperties();

        $default = $this->attribute->attribute(\eZStringType::DEFAULT_STRING_FIELD);
        if (!empty($default)){
            $schema['default'] = $default;
        }

        $maxLength = (int)$this->attribute->attribute(\eZStringType::MAX_LEN_FIELD);
        if ($maxLength > 0) {
            $schema['maxLength'] = $maxLength;
        }

        return $schema;
    }

}