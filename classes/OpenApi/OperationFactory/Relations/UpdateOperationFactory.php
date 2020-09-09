<?php

namespace Opencontent\OpenApi\OperationFactory\Relations;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;
use Opencontent\Opendata\Api\Values\Content;

class UpdateOperationFactory extends OperationFactory\UpdateOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        return \ezpI18n::tr('ocopenapi', 'Update the priority of an existing relationship in the %name field by related resource id', null, [
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws InvalidPayloadException
     * @throws NotFoundException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $payload = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];

        $currentPayload = $this->getCurrentPayload();
        if (!isset($currentPayload['priority'])){
            throw new InvalidPayloadException('priority', 'value is required');
        }
        $newPriority = (int)$currentPayload['priority'];

        $requestId = $this->getCurrentRequestParameter($endpointFactory->getParentOperationFactory()->getItemIdLabel());
        $requestIndex = $this->getCurrentRequestParameter($this->getItemIdLabel());

        $currentRelatedItem = false;
        $doUpdate = false;
        foreach ($payload as $index => $item) {
            if ($item['id'] == $requestIndex) {
                $currentRelatedItem = $item;
                if ($currentRelatedItem['priority'] != $newPriority){
                    $payload[$index]['priority'] = $newPriority;
                    $doUpdate = true;
                }
            }
        }
        if (!$currentRelatedItem) {
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
            $publishResult = $this->getContentRepository()->update($payloadBuilder->getArrayCopy());
            $content = new Content($publishResult['content']);
            $serializedContent = $this->getPropertyFactory()->serializeValue($content, $this->getCurrentRequestLanguage());
            foreach ($serializedContent as $index => $item) {
                if ($item['id'] == $requestIndex) {
                    $result->variables = $item;
                }
            }
        }

        return $result;
    }

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        /** @var RelationsSchemaFactory $schemaFactory */
        $schemaFactory = $this->getSchemaFactories()[0];

        $responseList = parent::generateResponseList();
        $responseList['200'] = new OA\Response('Successful response', [
            'application/json' => new OA\MediaType([
                'schema' => $schemaFactory->generateSchema()
            ])
        ], $this->generateResponseHeaders());

        return $responseList;
    }

    protected function generateOperationAdditionalProperties()
    {
        /** @var RelationsSchemaFactory $schemaFactory */
        $schemaFactory = $this->getSchemaFactories()[0];
        $schema = $schemaFactory->generateSchema();
        $schema->title = $schema->title . 'Struct';
        unset($schema->properties['id']);
        unset($schema->properties['uri']);
        $requestBody = new OA\RequestBody(['application/json' => new OA\MediaType([
            'schema' => $schema
        ])]);

        $properties = parent::generateOperationAdditionalProperties();
        $properties['requestBody'] = $requestBody;
        return $properties;
    }
}