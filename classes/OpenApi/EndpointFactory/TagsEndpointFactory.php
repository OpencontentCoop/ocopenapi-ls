<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\SchemaFactory;

class TagsEndpointFactory extends EndpointFactory
{
    public function provideSchemaFactories()
    {
        return [new SchemaFactory\TagSchemaFactory()];
    }

}