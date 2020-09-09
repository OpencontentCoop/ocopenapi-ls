<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class FloatFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = parent::provideProperties();
        $default = $this->attribute->attribute(\eZFloatType::DEFAULT_FIELD);
        if (!empty($default)){
            $schema['default'] = $default;
        }
        $schema['type'] = 'number';
        $schema['format'] = 'float';

        $min = $this->attribute->attribute(\eZFloatType::MIN_FIELD);
        $max = $this->attribute->attribute(\eZFloatType::MAX_FIELD);

        if ($min) {
            $schema["minimum"] = $min;
        }

        if ($max) {
            $schema["maximum"] = $max;
        }

        return $schema;
    }

    public function serializeValue(Content $content, $locale)
    {
        return (float)$this->getContent($content, $locale);
    }
}