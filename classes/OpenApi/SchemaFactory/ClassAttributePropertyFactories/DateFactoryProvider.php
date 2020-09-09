<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class DateFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $default = $this->attribute->attribute( \eZDateType::DEFAULT_FIELD );

        return array_merge_recursive($data, array(
            "format" => "date",
            "default" => $default == \eZDateType::DEFAULT_CURRENT_DATE ? date('c') : null
        ));
    }

}