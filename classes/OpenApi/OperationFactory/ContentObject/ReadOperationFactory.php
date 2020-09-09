<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\Exceptions\OutOfRangeException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Exception\ForbiddenException;

class ReadOperationFactory extends OperationFactory\ReadOperationFactory
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
        try {
            $search = $this->getSearchRepository($endpointFactory);
            $query = [];
            $query[] = 'classes [' . implode(',', $endpointFactory->getClassIdentifierList()) . ']';
            $query[] = 'subtree [' . $endpointFactory->getNodeId() . ']';
            $query[] = 'raw[meta_language_code_ms] in [' . $this->getCurrentRequestLanguage() . ']';
            $query[] = 'raw[meta_remote_id_ms] = \'' . $requestId . '\'';
            $query[] = 'limit 1';
            $query[] = 'offset 0';
            $query = implode(' and ', $query);

            $searchResult = $search->search($query);
            if ($searchResult->totalCount > 0){
                $result->variables = $searchResult->searchHits[0];
            }else{
                throw new NotFoundException($requestId);
            }
        }catch (OutOfRangeException $e){
            throw new NotFoundException($requestId, $e);
        }

        return $result;
    }
}