<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exceptions\InternalException;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\OperationFactory\CacheAwareInterface;
use Opencontent\Opendata\Api\Values\SearchResults;
use ezpRestMvcResult;

class SearchOperationFactory extends OperationFactory\SearchOperationFactory implements CacheAwareInterface
{
    use ContentRepositoryTrait;

    public function setResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): void
    {
        if ($endpointFactory instanceof EndpointFactory\NodeClassesEndpointFactory) {
            $nodeID = (int)$endpointFactory->getNodeId();
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
        $query = implode(' and ', $this->buildQueryParts($endpointFactory));
        $search = $this->getSearchRepository($endpointFactory);
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

    protected function buildQueryParts($endpointFactory)
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

        $query = [];
        if (!empty($searchTerm)) {
            $query[] = 'q = \'' . addcslashes($searchTerm, '\'()[]"') . '\'';
        }

        $query[] = 'classes [' . implode(',', $endpointFactory->getClassIdentifierList()) . ']';
        $query[] = 'subtree [' . $endpointFactory->getNodeId() . ']';
        $query[] = 'raw[meta_language_code_ms] in [' . $this->getCurrentRequestLanguage() . ']';
        $query[] = 'sort [published=>desc]';
        $query[] = 'limit ' . $limit;
        $query[] = 'offset ' . $offset;

        return $query;
    }

    protected function buildResult(SearchResults $searchResults, $path)
    {
        $parameters = [
            'offset' => 0,
            'limit' => 0,
        ];
        foreach ($this->generateSearchParameters() as $parameter) {
            if ($this->getCurrentRequestParameter($parameter->name)) {
                $parameters[$parameter->name] = $this->getCurrentRequestParameter($parameter->name);
            }
        }

        $result = [
            'items' => $searchResults->searchHits,
            'self' => $path . '?' . http_build_query($parameters),
            'prev' => null,
            'next' => null,
            'count' => $searchResults->totalCount,
//            'query' => $searchResults->query,
        ];

        if ($searchResults->nextPageQuery) {
            $nextParameters = $parameters;
            $nextParameters['offset'] += $nextParameters['limit'];
            $result['next'] = $path . '?' . http_build_query($nextParameters);
        }

        if ($parameters['offset'] > 0) {
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
