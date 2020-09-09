<?php

namespace Opencontent\OpenApi\OperationFactory\Relations;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;
use Opencontent\Opendata\Api\Values\Content;

class CreateOperationFactory extends OperationFactory\CreateOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        $resourceEndpointPath = RelationsSchemaFactory::getResourceEndpointPath($this->getPropertyFactory()->getAttribute()->attribute('id'), false);
        return \ezpI18n::tr('ocopenapi', 'Add new relationship between the resource (in the %name field) and a resources in the %store archive', null, [
            '%store' => rtrim($resourceEndpointPath, '/'),
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     * @throws InvalidPayloadException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $parentResult = $this->getParentOperationResult($endpointFactory);
        $payload = $parentResult->variables[$this->getPropertyFactory()->providePropertyIdentifier()];
        $currentPayload = $this->getCurrentPayload();
        if (!isset($currentPayload['uri'])){
            throw new InvalidPayloadException('uri', 'value is required');
        }
        $payload[] = $currentPayload;
        $newId = basename(parse_url($currentPayload['uri'], PHP_URL_PATH));

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
            if ($item['id'] == $newId) {
                $result->variables = $item;
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
        $properties = parent::generateOperationAdditionalProperties();
        /** @var RelationsSchemaFactory $schemaFactory */
        $schemaFactory = $this->getSchemaFactories()[0];
        $properties['requestBody'] = $schemaFactory->generateRequestBody();
        return $properties;
    }
}