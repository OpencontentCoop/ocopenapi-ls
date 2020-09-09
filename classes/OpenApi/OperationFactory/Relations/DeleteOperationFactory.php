<?php

namespace Opencontent\OpenApi\OperationFactory\Relations;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;
use Opencontent\Opendata\Api\Values\Content;

class DeleteOperationFactory extends OperationFactory\DeleteOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Delete an existing relationship in the %name field by related resource id', null, [
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
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

        $doUpdate = false;
        foreach ($payload as $index => $item) {
            if ($item['id'] == $requestIndex) {
                unset($payload[$index]);
                $doUpdate = true;
            }
        }
        if (!$doUpdate) {
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestIndex);
        }

        if ($doUpdate){
            $payloadBuilder = new OperationFactory\ContentObject\PayloadBuilder();
            $payloadBuilder->setRemoteId($parentResult->variables['id']);
            $this->getPropertyFactory()->serializePayload(
                $payloadBuilder,
                [$this->getPropertyFactory()->providePropertyIdentifier() => $payload],
                $this->getCurrentRequestLanguage()
            );
            $this->getContentRepository()->update($payloadBuilder->getArrayCopy());
        }

        return $result;
    }
}