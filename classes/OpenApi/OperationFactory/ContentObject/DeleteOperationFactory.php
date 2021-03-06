<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\Exceptions\OutOfRangeException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Exception\ForbiddenException;

class DeleteOperationFactory extends OperationFactory\DeleteOperationFactory
{
    use ContentRepositoryTrait;

    public function getSummary()
    {
        $resourceNames = [];
        foreach ($this->schemaFactories as $schemaFactory) {
            $resourceNames[] = $schemaFactory->getName();
        }
        $or = \ezpI18n::tr('ocopenapi', ' or ');
        $resourceName = implode($or, $resourceNames);
        return \ezpI18n::tr('ocopenapi', 'Delete an existing %name resource by id', null, ['%name' => $resourceName]);
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws ForbiddenException
     * @throws InvalidParameterException
     * @throws NotFoundException
     * @throws \Opencontent\OpenApi\Exceptions\InvalidPayloadException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();

        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)) {
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }
        $this->getResource($endpointFactory, $requestId);

        $moveToTrash = $this->getCurrentRequestParameter('trash') == "true";

        $object = \eZContentObject::fetchByRemoteID($requestId);
        if (!$object instanceof \eZContentObject) {
            throw new NotFoundException($requestId);
        }
        if (\eZContentObjectTrashNode::fetchByContentObjectID($object->attribute('id'))) {
            throw new NotFoundException($requestId);
        }
        if (!$object->canRemove()) {
            throw new ForbiddenException($requestId, 'delete');
        }

        $deleteIDArray = array();
        foreach ($object->assignedNodes() as $node) {
            $deleteIDArray[] = $node->attribute('node_id');
        }
        if (!empty($deleteIDArray)) {
            if (\eZOperationHandler::operationIsAvailable('content_delete')) {
                \eZOperationHandler::execute('content',
                    'delete',
                    array(
                        'node_id_list' => $deleteIDArray,
                        'move_to_trash' => $moveToTrash
                    ),
                    null, true);
            } else {
                \eZContentOperationCollection::deleteObject($deleteIDArray, $moveToTrash);
            }
        }

        return $result;
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        $properties['parameters'][] = new OA\Parameter('trash', OA\Parameter::IN_QUERY, 'If is true move resource to trash else delete permanently from store', [
            'schema' => $this->generateSchemaProperty(['type' => 'boolean', 'default' => false]),
        ]);

        return $properties;
    }
}