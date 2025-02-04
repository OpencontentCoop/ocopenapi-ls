<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\OperationFactory\CacheAwareInterface;
use ezpRestMvcResult;

class ReadOperationFactory extends OperationFactory\ReadOperationFactory implements CacheAwareInterface
{
    use ContentRepositoryTrait;

    private $currentNodeId = null;

    public function setResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): void
    {
        if ($endpointFactory instanceof EndpointFactory\NodeClassesEndpointFactory) {
            $db = \eZDB::instance();
            $remoteId = $db->escapeString($result->variables['id']);
            $query = "SELECT node_id FROM ezcontentobject_tree 
                WHERE contentobject_id in (SELECT id FROM ezcontentobject WHERE remote_id = '$remoteId') 
                AND main_node_id = node_id";
            $nodeListArray = $db->arrayQuery($query);
            $nodeID = $nodeListArray[0]['node_id'] ?? 0;
            header("Cache-Control: public, must-revalidate, max-age=10, s-maxage=259200"); //@todo make configurable
            header("X-Cache-Tags: node-{$nodeID}");
            header("Vary: X-User-Context-Hash");
            header("Vary: Accept-Language");
        }
    }

    public function hasResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): bool
    {
        return $endpointFactory instanceof EndpointFactory\NodeClassesEndpointFactory;
    }

    public function getSummary()
    {
        $resourceNames = [];
        foreach ($this->schemaFactories as $schemaFactory){
            $resourceNames[] = $schemaFactory->getName();
        }
        $or = \ezpI18n::tr('ocopenapi', ' or ');
        $resourceName = implode($or, $resourceNames);
        return \ezpI18n::tr('ocopenapi', 'Find an existing %name resource by id', null, ['%name' => $resourceName] );
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws InvalidParameterException
     * @throws NotFoundException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)){
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }

        $result->variables = $this->getResource($endpointFactory, $requestId, $this->getCurrentRequestLanguage());

        return $result;
    }
}
