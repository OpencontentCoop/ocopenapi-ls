<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class TimeFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $default = $this->attribute->attribute(\eZTimeType::DEFAULT_FIELD);

        return array_merge_recursive($data, array(
            "format" => "time",
            "default" => $default == \eZTimeType::DEFAULT_CURRENT_DATE ? date('H:i') : null
        ));
    }

}