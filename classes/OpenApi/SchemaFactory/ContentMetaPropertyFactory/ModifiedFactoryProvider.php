<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;

use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class ModifiedFactoryProvider extends ContentMetaPropertyFactory
{
    /**
     * @return array
     */
    public function provideProperties()
    {
        return [
            "type" => "string",
            "format" => "date",
            "description" => \ezpI18n::tr( 'ocopenapi', "Resource last modification date"),
            "readOnly" => true,
        ];
    }

    public function providePropertyIdentifier()
    {
        return 'modified_at';
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        //is readonly!
        if (!empty($payload[$this->providePropertyIdentifier()]) && !$payloadBuilder->isAction(PayloadBuilder::TRANSLATE)) {
            throw new InvalidPayloadException("Field {$this->providePropertyIdentifier()} is read only");
//            $timestamp = strtotime($payload[$this->providePropertyIdentifier()]);
//            if ($timestamp) {
//                $payloadBuilder->setModified($timestamp);
//            }
        }
    }
}
