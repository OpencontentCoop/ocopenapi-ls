<?php

namespace Opencontent\OpenApi\OperationFactory\Matrix;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\MatrixSchemaFactory;
use Opencontent\Opendata\Api\Values\Content;

class UpdateOperationFactory extends OperationFactory\UpdateOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Update an existing row in the %name table by row index', null, [
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws InvalidParameterException
     * @throws NotFoundException
     * @throws \Opencontent\OpenApi\Exceptions\InvalidPayloadException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $payload = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];

        $requestId = $this->getCurrentRequestParameter($endpointFactory->getParentOperationFactory()->getItemIdLabel());
        $requestIndex = $this->getCurrentRequestParameter($this->getItemIdLabel());

        if ((string)intval($requestIndex) != $requestIndex){
            throw new InvalidParameterException($this->getItemIdLabel(), $requestIndex);
        }
        if (!isset($payload[(int)$requestIndex])){
            throw new NotFoundException($requestId . '/' . $this->getPropertyFactory()->providePropertyIdentifier() . '#' . $requestIndex);
        }
        $payload[(int)$requestIndex] = $this->getCurrentPayload();

        $payloadBuilder = new OperationFactory\ContentObject\PayloadBuilder();
        $payloadBuilder->setRemoteId($parentResult->variables['id']);
        $this->getPropertyFactory()->serializePayload(
            $payloadBuilder,
            [$this->getPropertyFactory()->providePropertyIdentifier() => $payload],
            $this->getCurrentRequestLanguage()
        );

        $publishResult = $this->getContentRepository()->update($payloadBuilder->getArrayCopy());
        $content = new Content($publishResult['content']);
        $serializedContent = $this->getPropertyFactory()->serializeValue($content, $this->getCurrentRequestLanguage());
        $result->variables = $serializedContent[(int)$requestIndex];

        return $result;
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        /** @var MatrixSchemaFactory $schemaFactory */
        $schemaFactory = $this->getSchemaFactories()[0];
        $properties['requestBody'] = $schemaFactory->generateRequestBody();
        return $properties;
    }
}