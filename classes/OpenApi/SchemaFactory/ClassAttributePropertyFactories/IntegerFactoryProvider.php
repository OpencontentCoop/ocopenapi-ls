<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class IntegerFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $schema = parent::provideProperties();
        $default = $this->attribute->attribute(\eZIntegerType::DEFAULT_VALUE_FIELD);
        if (!empty($default)){
            $schema['default'] = $default;
        }
        $schema['type'] = 'integer';
        $schema['format'] = 'int32';

        $inputState = $this->attribute->attribute( \eZIntegerType::INPUT_STATE_FIELD );

        $min = $this->attribute->attribute(\eZIntegerType::MIN_VALUE_FIELD);
        $max = $this->attribute->attribute(\eZIntegerType::MAX_VALUE_FIELD);

        if ($inputState != \eZIntegerType::NO_MIN_MAX_VALUE) {

            if ($inputState == \eZIntegerType::HAS_MIN_VALUE || $inputState == \eZIntegerType::HAS_MIN_MAX_VALUE) {
                $schema["minimum"] = (int)$min;
            }

            if ($inputState == \eZIntegerType::HAS_MAX_VALUE || $inputState == \eZIntegerType::HAS_MIN_MAX_VALUE) {
                $schema["maximum"] = (int)$max;
            }
        }

        return $schema;
    }

    public function serializeValue(Content $content, $locale)
    {
        return (int)$this->getContent($content, $locale);
    }
}