<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory;

use Opencontent\OpenApi\Loader;
use Opencontent\Opendata\Api\Values\Content;

class SlugClassesUriFactoryProvider extends UriFactoryProvider
{
    public function serializeValue(Content $content, $locale)
    {
        $resourceEndpointPath = $this->getContextEndpoint()->getPath();
        $resourceEndpointPath = $this->getContextEndpoint()->replacePrefix($resourceEndpointPath);

        if (strpos($resourceEndpointPath, '{') !== false) {
            $resourceEndpointPathParts = explode('/', $resourceEndpointPath);
            array_pop($resourceEndpointPathParts);
            $resourceEndpointPath =  implode('/', $resourceEndpointPathParts);
        }

        return Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl . $resourceEndpointPath
            . '/' . $content->metadata->remoteId
            . '#' . \eZCharTransform::instance()->transformByGroup($content->metadata->name[$locale], 'urlalias');

    }
}
