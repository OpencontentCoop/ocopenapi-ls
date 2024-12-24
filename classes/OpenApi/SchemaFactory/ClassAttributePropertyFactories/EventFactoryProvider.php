<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class EventFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return [
            "type" => "string",
            "nullable" => true,
            "description" => $this->getPropertyDescription() . ' (iCalendar RFC pattern rule)',
            "example" => "DTSTART=20200115T090000Z;DTEND=20200115T110000Z;FREQ=DAILY;INTERVAL=1;UNTIL=20200130T230000Z"
        ];
    }

    public function serializeValue(Content $content, $locale)
    {
        $content = $this->getContent($content, $locale);
        if (isset($content['input']['recurrencePattern'])){
            return $content['input']['recurrencePattern'];
        }

        return null;
    }
}