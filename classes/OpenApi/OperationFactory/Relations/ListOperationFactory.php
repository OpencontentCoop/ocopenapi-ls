<?php

namespace Opencontent\OpenApi\OperationFactory\Relations;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;

class ListOperationFactory extends OperationFactory\ListOperationFactory
{
    use OperationFactory\ChildOperationFactoryTrait;

    public function getSummary()
    {
        $resourceEndpointPath = RelationsSchemaFactory::getResourceEndpointPath($this->getPropertyFactory()->getAttribute()->attribute('id'), false);
        return \ezpI18n::tr('ocopenapi', 'Lists the relationship between the resource (in the %name field) and the resources in the %store archive', null, [
            '%store' => rtrim($resourceEndpointPath, '/'),
            '%name' => $this->getPropertyFactory()->providePropertyIdentifier()
        ]);
    }

    /**
     * @param EndpointFactory\RelationsEndpointFactory $endpointFactory
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


    protected function generateListSchema()
    {
        /** @var RelationsSchemaFactory $schemaFactory */
        $schemaFactory = $this->getSchemaFactories()[0];

        $searchResultSchema = new OA\Schema();
        $searchResultSchema->type = 'object';
        $searchResultSchema->properties = [
            'items' => $this->generateSchemaProperty(['type' => 'array', 'items' => $schemaFactory->generateSchema()]),
        ];

        return $searchResultSchema;
    }
}