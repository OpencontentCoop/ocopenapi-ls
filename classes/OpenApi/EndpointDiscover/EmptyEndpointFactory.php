<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;

class EmptyEndpointFactory extends EndpointFactoryProvider
{
    public function getEndpointFactoryCollection()
    {
        return new EndpointFactoryCollection();
    }

}