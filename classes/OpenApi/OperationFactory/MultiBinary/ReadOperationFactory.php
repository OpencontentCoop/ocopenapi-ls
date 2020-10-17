<?php

namespace Opencontent\OpenApi\OperationFactory\MultiBinary;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;

class ReadOperationFactory extends OperationFactory\ReadOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

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
        $requestFilename = $this->getCurrentRequestParameter($this->getItemIdLabel());

        $filenames = array_column($data, 'filename');
        if (!in_array($requestFilename, $filenames)){
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestFilename);
        }
        foreach ($data as $item){
            if ($item['filename'] == $requestFilename){
                $result->variables = $item;
            }
        }

        return $result;
    }
}