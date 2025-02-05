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
            "example" => "FREQ=DAILY;UNTIL=20171215T235900;DTSTART=20171205T060000;DTEND=20171205T063000;INTERVAL=1",
        ];
    }

    public function serializeValue(Content $content, $locale)
    {
        $content = $this->getContent($content, $locale);
        if (isset($content['input']['recurrencePattern'])) {
            try {
                if (!class_exists('\Recurr\Rule')) {
                    throw new \Exception('Class "Recurr\Rule" not found');
                }
                $rrule = new \Recurr\Rule($content['input']['recurrencePattern']);
                return $rrule->getString();
            }catch (\Throwable $e){
                \eZDebug::writeError($e->getMessage(), __METHOD__);
                return $content['input']['recurrencePattern'];
            }
        }

        return null;
    }
}