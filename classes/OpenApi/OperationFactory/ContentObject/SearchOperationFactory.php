<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InternalException;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\Opendata\Api\Values\SearchResults;

class SearchOperationFactory extends OperationFactory\SearchOperationFactory
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
        return \ezpI18n::tr('ocopenapi', 'Search for %name resources', null, ['%name' => $resourceName] );
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws InternalException
     * @throws InvalidParameterException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $searchTerm = $this->getCurrentRequestParameter('searchTerm');
        $limit = (int)$this->getCurrentRequestParameter('limit');
        $offset = (int)$this->getCurrentRequestParameter('offset');

        if ($limit <= 0 || $limit > self::MAX_LIMIT) {
            throw new InvalidParameterException('limit', $limit);
        }
        if ($offset < 0) {
            throw new InvalidParameterException('offset', $offset);
        }

        $search = $this->getSearchRepository($endpointFactory);

        $query = [];
        if (!empty($searchTerm)) {
            $query[] = 'q = \'' . addcslashes($searchTerm, '\'()[]"') . '\'';
        }

        $query[] = 'classes [' . implode(',', $endpointFactory->getClassIdentifierList()) . ']';
        $query[] = 'subtree [' . $endpointFactory->getNodeId() . ']';
        $query[] = 'raw[meta_language_code_ms] in [' . $this->getCurrentRequestLanguage() . ']';
        $query[] = 'limit ' . $limit;
        $query[] = 'offset ' . $offset;
        $query = implode(' and ', $query);

        try {
            $path = $endpointFactory->getBaseUri() . $endpointFactory->getPath();
            $result = new \ezpRestMvcResult();
            \eZINI::instance('ezfind.ini')->setVariable('LanguageSearch', 'SearchMainLanguageOnly', 'disabled');
            $result->variables = $this->buildResult($search->search($query), $path);
        } catch (\Exception $e) {
            throw new InternalException($e->getMessage() . ' on ' . $query);
        }

        return $result;
    }

    protected function buildResult(SearchResults $searchResults, $path)
    {
        $result = [
            'items' => $searchResults->searchHits,
            'self' => null,
            'prev' => null,
            'next' => null,
            'count' => $searchResults->totalCount,
//            'query' => $searchResults->query,
        ];

        $parameters = [];
        foreach ($this->generateSearchParameters() as $parameter) {
            if ($this->getCurrentRequestParameter($parameter->name)) {
                $parameters[$parameter->name] = $this->getCurrentRequestParameter($parameter->name);
            }
        }

        $result['self'] = $path . '?' . http_build_query($parameters);

        if ($searchResults->nextPageQuery) {
            $nextParameters = $parameters;
            $nextParameters['offset'] += $nextParameters['limit'];
            $result['next'] = $path . '?' . http_build_query($nextParameters);
        }

        if (isset($parameters['offset']) && $parameters['offset'] > 0) {
            $prevParameters = $parameters;
            $prevParameters['offset'] -= $prevParameters['limit'];
            if ($prevParameters['offset'] < 0) {
                $prevParameters['offset'] = 0;
            }
            $result['prev'] = $path . '?' . http_build_query($prevParameters);
        }

        return $result;
    }
}