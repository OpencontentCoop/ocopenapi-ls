<?php

namespace Opencontent\OpenApi\OperationFactory\MultiBinary;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\OperationFactory;

class ListOperationFactory extends OperationFactory\ListOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'List binary files the %name field', null, ['%name' => $this->getPropertyFactory()->providePropertyIdentifier()]);
    }

    /**
     * @param EndpointFactory\MatrixEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $result->variables = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];

        return $result;
    }
}