<?php

namespace Opencontent\OpenApi;

use Opencontent\OpenApi\Exceptions\OperationNotFoundException;

abstract class EndpointFactoryProvider implements EndpointFactoryProviderInterface
{
    /**
     * @param $operationId
     * @return OperationFactory
     * @throws OperationNotFoundException
     */
    public function getOperationFactoryById($operationId)
    {
        foreach ($this->getEndpointFactoryCollection() as $endpoint) {
            foreach ($endpoint->getOperationFactoryCollection()->getOperationFactories() as $operationFactory) {
                if ($operationFactory->getId() == $operationId) {
                    return $operationFactory;
                }
            }
        }

        throw new OperationNotFoundException($operationId);
    }

    /**
     * @param $operationId
     * @return EndpointFactory
     * @throws OperationNotFoundException
     */
    public function getEndpointFactoryByOperationId($operationId)
    {
        foreach ($this->getEndpointFactoryCollection() as $endpoint) {
            foreach ($endpoint->getOperationFactoryCollection()->getOperationFactories() as $operationFactory) {
                if ($operationFactory->getId() == $operationId) {
                    return $endpoint;
                }
            }
        }

        throw new OperationNotFoundException($operationId);
    }
}