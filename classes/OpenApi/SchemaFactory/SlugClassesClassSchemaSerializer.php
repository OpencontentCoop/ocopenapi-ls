<?php

namespace Opencontent\OpenApi\SchemaFactory;

use Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\SlugClassesUriFactoryProvider;

class SlugClassesClassSchemaSerializer extends ContentClassSchemaSerializer
{
    public static function loadContentMetaPropertyFactory($class, $identifier)
    {
        if ($identifier === 'uri') {
            return new SlugClassesUriFactoryProvider($class, $identifier);
        }

        return parent::loadContentMetaPropertyFactory($class, $identifier);
    }

}
