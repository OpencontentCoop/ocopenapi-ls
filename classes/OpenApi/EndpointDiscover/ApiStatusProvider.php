<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactory\ApiStatusEndpointFactory;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\OperationFactory\ApiStatusOperationFactory;
use Opencontent\OpenApi\OperationFactoryCollection;

class ApiStatusProvider extends EndpointFactoryProvider
{
    public function getEndpointFactoryCollection()
    {
        return new EndpointFactoryCollection([
            (new ApiStatusEndpointFactory())
                ->setPath('/status')
                ->setTags(['status'])
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        new ApiStatusOperationFactory()
                    ])
                )
        ]);
    }

}