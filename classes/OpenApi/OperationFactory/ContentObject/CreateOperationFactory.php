<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\CreateContentException as OpenApiCreateContentException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Exception\CreateContentException;
use Opencontent\Opendata\Api\Exception\DuplicateRemoteIdException;

class CreateOperationFactory extends OperationFactory\CreateOperationFactory
{
    use ContentRepositoryTrait;

    public function getSummary()
    {
        $resourceNames = [];
        foreach ($this->schemaFactories as $schemaFactory){
            $resourceNames[] = $schemaFactory->getName();
        }
        $or = \ezpI18n::tr('ocopenapi', ' or ');
        $resourceName = implode($or, $resourceNames);
        return \ezpI18n::tr('ocopenapi', 'Add new %name resource', null, ['%name' => $resourceName] );
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws OpenApiCreateContentException
     * @throws \Opencontent\OpenApi\Exceptions\InvalidPayloadException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $payload = $this->getCurrentPayload();
        try {
            $response = $this->getContentRepository($endpointFactory)->create($payload);
            $result->variables = $response['content'];
        } catch (CreateContentException $e) {
            throw new OpenApiCreateContentException($e->getMessage(), $e->getCode(), $e);
        } catch (DuplicateRemoteIdException $e){
            throw new OpenApiCreateContentException("Content with id '{$payload['id']}' already exists", $e->getCode(), $e);
        }

        return $result;
    }
}