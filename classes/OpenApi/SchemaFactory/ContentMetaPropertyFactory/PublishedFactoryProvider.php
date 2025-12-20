<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;

use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class PublishedFactoryProvider extends ContentMetaPropertyFactory
{
    /**
     * @return array
     */
    public function provideProperties()
    {
        return [
            "type" => "string",
            "format" => "date-time",
            "description" => \ezpI18n::tr( 'ocopenapi',"Resource publication date")
        ];
    }

    public function providePropertyIdentifier()
    {
        return 'published_at';
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (!empty($payload[$this->providePropertyIdentifier()]) && !$payloadBuilder->isAction(PayloadBuilder::TRANSLATE)) {
            $timestamp = strtotime($payload[$this->providePropertyIdentifier()]);
            if ($timestamp) {
                $payloadBuilder->setPublished($timestamp);
            }
        }
    }
}
