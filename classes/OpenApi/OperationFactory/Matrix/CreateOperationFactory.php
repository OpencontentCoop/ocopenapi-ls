<?php

namespace Opencontent\OpenApi\OperationFactory\Matrix;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\MatrixSchemaFactory;
use Opencontent\Opendata\Api\Values\Content;

class CreateOperationFactory extends OperationFactory\CreateOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Add a row in the %name table', null, ['%name' => $this->getPropertyFactory()->providePropertyIdentifier()]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws \Opencontent\OpenApi\Exceptions\InvalidPayloadException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $payload = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];
        $currentPayload = $this->getCurrentPayload();
        $payload[] = $currentPayload;

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

        if (isset($currentPayload['index']) && isset($serializedContent[$currentPayload['index']])) {
            $newItem = $serializedContent[$currentPayload['index']];
        } else {
            $newItem = array_pop($serializedContent);
        }

        $result->variables = $newItem;

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