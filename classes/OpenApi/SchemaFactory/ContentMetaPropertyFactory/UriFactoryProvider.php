<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;

use Opencontent\OpenApi\EndpointFactory\NodeClassesEndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Loader;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\OperationFactory\ContentObject\ReadOperationFactory;
use Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class UriFactoryProvider extends ContentMetaPropertyFactory
{
    private static $resourceEndpointPaths;

    /**
     * @return array
     */
    public function provideProperties()
    {
        return [
            "type" => "string",
            "description" => \ezpI18n::tr( 'ocopenapi', 'Resource Uniform Resource Identifier'),
            "readOnly" => true,
        ];
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (!empty($payload[$this->providePropertyIdentifier()]) && $payloadBuilder->action != PayloadBuilder::TRANSLATE) {
            throw new InvalidPayloadException("Field {$this->providePropertyIdentifier()} is read only");
        }
    }

    public function providePropertyIdentifier()
    {
        return 'uri';
    }

    public function serializeValue(Content $content, $locale)
    {
        return $this->getResourceEndpointPathForClassIdentifier($content->metadata->classIdentifier)
            . $content->metadata->remoteId
            . '#' . \eZCharTransform::instance()->transformByGroup($content->metadata->name[$locale], 'urlalias');
    }

    private function getResourceEndpointPathForClassIdentifier($classIdentifier)
    {
        if (!isset(self::$resourceEndpointPaths[$classIdentifier])) {
            self::$resourceEndpointPaths[$classIdentifier] = '/';
            $endpoint = Loader::instance()->getEndpointProvider()->getEndpointFactoryCollection()->findOneByCallback(function ($endpoint) use ($classIdentifier) {
                if ($endpoint instanceof NodeClassesEndpointFactory) {
                    if ($endpoint->getOperationByMethod('get') instanceof ReadOperationFactory
                        && in_array($classIdentifier, $endpoint->getClassIdentifierList())) {
                        return true;
                    }
                }
                return false;
            });
            if ($endpoint instanceof NodeClassesEndpointFactory) {
                $resourceEndpointPathParts = explode('/', $endpoint->getPath());
                array_pop($resourceEndpointPathParts);
                self::$resourceEndpointPaths[$classIdentifier] = Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl . implode('/', $resourceEndpointPathParts) . '/';
            }
        }

        return self::$resourceEndpointPaths[$classIdentifier];
    }

}