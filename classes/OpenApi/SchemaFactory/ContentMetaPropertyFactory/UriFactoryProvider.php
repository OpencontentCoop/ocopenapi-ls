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
            "description" => \ezpI18n::tr( 'ocopenapi', 'Api Uniform Resource Identifier'),
            "readOnly" => true,
            "nullable" => true,
        ];
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (!empty($payload[$this->providePropertyIdentifier()]) && !$payloadBuilder->isAction(PayloadBuilder::TRANSLATE)) {
            throw new InvalidPayloadException("Field {$this->providePropertyIdentifier()} is read only");
        }
    }

    public function providePropertyIdentifier()
    {
        return 'uri';
    }

    public function serializeValue(Content $content, $locale)
    {
        $pathArray = explode('/', $content->metadata->assignedNodes[0]['path_string']);
        $parentNode = $content->metadata->parentNodes[0];
        $resourcePath = $this->getResourceEndpointPathForClassIdentifier($content->metadata->classIdentifier, $parentNode, $pathArray);
        return $resourcePath ? $resourcePath . $content->metadata->remoteId
            . '#' . \eZCharTransform::instance()->transformByGroup($content->metadata->name[$locale], 'urlalias') : null;
    }

    protected function getResourceEndpointPathForClassIdentifier($classIdentifier, $parentNode, $pathArray)
    {
        if ($this->getContextEndpoint() instanceof NodeClassesEndpointFactory){
            $resourceEndpointPath = $this->getContextEndpoint()->getPath();
            if (strpos($resourceEndpointPath, '{') !== false) {
                $resourceEndpointPathParts = explode('/', $resourceEndpointPath);
                array_pop($resourceEndpointPathParts);
                $resourceEndpointPath =  implode('/', $resourceEndpointPathParts);
            }

            return Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl . $resourceEndpointPath . '/';
        }
        if (!is_numeric($parentNode)){
            $parentNode = $parentNode['id'] ?? 0;
        }
        if (!isset(self::$resourceEndpointPaths[$classIdentifier.$parentNode])) {
            self::$resourceEndpointPaths[$classIdentifier.$parentNode] = null;
            $endpoint = Loader::instance()->getEndpointProvider()->getEndpointFactoryCollection()->findOneByCallback(function ($endpoint) use ($classIdentifier, $pathArray) {
                if ($endpoint instanceof NodeClassesEndpointFactory) {
                    $parentNodeId = $endpoint->getNodeId();
                    if ($endpoint->getOperationByMethod('get') instanceof ReadOperationFactory
                        && in_array($parentNodeId, $pathArray)
                        && in_array($classIdentifier, $endpoint->getClassIdentifierList())) {
                        return true;
                    }
                }
                return false;
            });
            if ($endpoint instanceof NodeClassesEndpointFactory) {
                $resourceEndpointPathParts = explode('/', $endpoint->getPath());
                array_pop($resourceEndpointPathParts);
                self::$resourceEndpointPaths[$classIdentifier.$parentNode] = Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl . implode('/', $resourceEndpointPathParts) . '/';
            }
        }

        return self::$resourceEndpointPaths[$classIdentifier.$parentNode];
    }

}
