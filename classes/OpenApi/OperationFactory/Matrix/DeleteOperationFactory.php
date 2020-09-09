<?php

namespace Opencontent\OpenApi\OperationFactory\Matrix;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;

class DeleteOperationFactory extends OperationFactory\DeleteOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Delete an existing row in the %name table by row index', null, ['%name' => $this->getPropertyFactory()->providePropertyIdentifier()]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws InvalidParameterException
     * @throws NotFoundException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $payload = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];

        $requestId = $this->getCurrentRequestParameter($endpointFactory->getParentOperationFactory()->getItemIdLabel());
        $requestIndex = $this->getCurrentRequestParameter($this->getItemIdLabel());

        if ((string)intval($requestIndex) != $requestIndex) {
            throw new InvalidParameterException($this->getItemIdLabel(), $requestIndex);
        }
        if (!isset($payload[(int)$requestIndex])) {
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestIndex);
        }
        unset($payload[(int)$requestIndex]);

        $payloadBuilder = new OperationFactory\ContentObject\PayloadBuilder();
        $payloadBuilder->setRemoteId($parentResult->variables['id']);
        $this->getPropertyFactory()->serializePayload(
            $payloadBuilder,
            [$this->getPropertyFactory()->providePropertyIdentifier() => $payload],
            $this->getCurrentRequestLanguage()
        );

        $this->getContentRepository()->update($payloadBuilder->getArrayCopy());

        return $result;
    }
}