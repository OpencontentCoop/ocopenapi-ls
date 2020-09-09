<?php

namespace Opencontent\OpenApi\OperationFactory\Matrix;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;

class ReadOperationFactory extends OperationFactory\ReadOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Find an existing row in the %name table by row index', null, [
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws InvalidParameterException
     * @throws NotFoundException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $data = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];

        $requestId = $this->getCurrentRequestParameter($endpointFactory->getParentOperationFactory()->getItemIdLabel());
        $requestIndex = $this->getCurrentRequestParameter($this->getItemIdLabel());

        if ((string)intval($requestIndex) != $requestIndex){
            throw new InvalidParameterException($this->getItemIdLabel(), $requestIndex);
        }
        if (!isset($data[(int)$requestIndex])){
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestIndex);
        }
        $result->variables = $data[(int)$requestIndex];

        return $result;
    }
}