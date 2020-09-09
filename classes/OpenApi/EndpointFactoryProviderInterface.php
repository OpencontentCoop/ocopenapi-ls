<?php

namespace Opencontent\OpenApi;

use Opencontent\OpenApi\Exceptions\OperationNotFoundException;

interface EndpointFactoryProviderInterface
{
    /**
     * @return EndpointFactoryCollection
     */
    public function getEndpointFactoryCollection();


    /**
     * @param $operationId
     * @return OperationFactory
     * @throws OperationNotFoundException
     */
    public function getOperationFactoryById($operationId);

    /**
     * @param $operationId
     * @return EndpointFactory
     * @throws OperationNotFoundException
     */
    public function getEndpointFactoryByOperationId($operationId);
}