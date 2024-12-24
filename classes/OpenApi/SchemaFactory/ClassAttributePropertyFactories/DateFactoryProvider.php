<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class DateFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        $data = parent::provideProperties();
        $data['nullable'] = true;
        $default = $this->attribute->attribute( \eZDateType::DEFAULT_FIELD );

        return array_merge_recursive($data, array(
            "format" => "date",
            // 'o' is ISO-8601 week-numbering year. This has the same value as Y,
            // except that if the ISO week number (W) belongs to the previous or next year, that year is used instead.
            "default" => $default == \eZDateType::DEFAULT_CURRENT_DATE ? date('o-m-d') : null
        ));
    }

}