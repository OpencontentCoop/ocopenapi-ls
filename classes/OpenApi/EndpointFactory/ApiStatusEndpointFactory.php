<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\SchemaFactory;

class ApiStatusEndpointFactory extends EndpointFactory
{
    public function provideSchemaFactories()
    {
        return [
            new SchemaFactory\ApiStatusSchemaFactory()
        ];
    }

}