<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;

use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;

class RemoteIdFactoryProvider extends ContentMetaPropertyFactory
{
    public function providePropertyIdentifier()
    {
        return 'id';
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if ($payloadBuilder->action != PayloadBuilder::TRANSLATE) {
            if (isset($payload['_id']) && ($payloadBuilder->action == PayloadBuilder::UPDATE || $payloadBuilder->action == PayloadBuilder::PATCH)) {
                $payloadBuilder->setId($payload['_id']);
            }

            if (isset($payload['id'])) {
                if ($payloadBuilder->action == PayloadBuilder::CREATE) {
                    $payloadBuilder->setRemoteId($payload['id']);

                } elseif ($payloadBuilder->action == PayloadBuilder::UPDATE || $payloadBuilder->action == PayloadBuilder::PATCH) {

                    $alreadyExists = \eZContentObject::fetchByRemoteID($payload['id']);
                    if ($alreadyExists instanceof \eZContentObject && (int)$alreadyExists->attribute('id') !== (int)$payload['_id']) {
                        throw new InvalidParameterException('id', $payload['id']);
                    }

                    $payloadBuilder->setOption('update_remote_id', $payload['id']);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function provideProperties()
    {
        return [
            "type" => "string",
            "description" => \ezpI18n::tr( 'ocopenapi',"Resource id (the value is unique across all resources)")
        ];
    }

}