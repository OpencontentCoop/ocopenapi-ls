<?php


namespace Opencontent\OpenApi\EndpointFactory;


use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\OperationFactory;

interface ChildEndpointFactoryInterface
{
    /**
     * @param OperationFactory $parentOperationFactory
     * @return ChildEndpointFactoryInterface
     */
    public function setParentOperationFactory($parentOperationFactory);

    /**
     * @return EndpointFactory
     */
    public function getParentEndpointFactory();

    /**
     * @param NodeClassesEndpointFactory $parentEndpoint
     * @return ChildEndpointFactoryInterface
     */
    public function setParentEndpointFactory($parentEndpoint);

    /**
     * @return OperationFactory
     */
    public function getParentOperationFactory();
}