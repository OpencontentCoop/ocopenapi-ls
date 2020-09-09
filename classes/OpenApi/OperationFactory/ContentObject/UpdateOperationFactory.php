<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\Exceptions\OutOfRangeException;
use Opencontent\OpenApi\Exceptions\UpdateContentException as OpenApiUpdateContentException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Exception\CreateContentException;
use Opencontent\Opendata\Api\Exception\UpdateContentException;

class UpdateOperationFactory extends OperationFactory\UpdateOperationFactory
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
        return \ezpI18n::tr('ocopenapi', 'Update an existing %name resource', null, ['%name' => $resourceName] );
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $payload = $this->getCurrentPayload();
        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)){
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }

        $object = \eZContentObject::fetchByRemoteID($requestId);
        if (!$object instanceof \eZContentObject) {
            throw new NotFoundException($requestId);
        }
        if (isset($payload['_id'])){
            throw new InvalidPayloadException('Invalid field _id');
        }
        $payload['_id'] = (int)$object->attribute('id');

        try {
            $response = $this->getContentRepository($endpointFactory)->update($payload);
            $result->variables = $response['content'];
        } catch (UpdateContentException $e) {
            throw new OpenApiUpdateContentException($e->getMessage(), $e->getCode(), $e);
        } catch (CreateContentException $e) {
            throw new OpenApiUpdateContentException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }
}