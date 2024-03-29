<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\Exceptions\OutOfRangeException;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\ContentSearch;

trait ContentRepositoryTrait
{
    protected $contentRepository;

    protected $searchRepository;

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return ContentRepository
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function getContentRepository($endpointFactory)
    {
        if ($this->contentRepository === null) {
            $this->contentRepository = new ContentRepository();
            $currentEnvironment = new \OpenApiEnvironmentSettings($endpointFactory, $this->getSchemaFactories(), $this->getCurrentRequestLanguage());
            $this->contentRepository->setEnvironment($currentEnvironment);
            $currentEnvironment->__set('request', $this->getCurrentRequest());
        }

        return $this->contentRepository;
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return ContentSearch
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function getSearchRepository($endpointFactory)
    {
        if ($this->searchRepository === null) {
            $this->searchRepository = new ContentSearch();
            $currentEnvironment = new \OpenApiEnvironmentSettings($endpointFactory, $this->getSchemaFactories(), $this->getCurrentRequestLanguage());
            $this->searchRepository->setEnvironment($currentEnvironment);
            $currentEnvironment->__set('request', $this->getCurrentRequest());
        }

        return $this->searchRepository;
    }

    /**
     * @param $endpointFactory
     * @param $requestId
     * @return \Opencontent\Opendata\Api\Values\Content|array
     * @throws NotFoundException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function getResource($endpointFactory, $requestId, $language = false)
    {
        try {
            $search = $this->getSearchRepository($endpointFactory);
            $query = [];
            $query[] = 'classes [' . implode(',', $endpointFactory->getClassIdentifierList()) . ']';
            $query[] = 'subtree [' . $endpointFactory->getNodeId() . ']';
            if ($language) {
                $query[] = 'raw[meta_language_code_ms] in [' . $language . ']';
            }
            $query[] = 'raw[meta_remote_id_ms] = \'' . $requestId . '\'';
            $query[] = 'limit 1';
            $query[] = 'offset 0';
            $query = implode(' and ', $query);

            $searchResult = $search->search($query);

            $errorMessage = $requestId;
            if ($language){
                $errorMessage .= " in $language";
            }

            if ($searchResult->totalCount > 0){
                return $searchResult->searchHits[0];
            }else{
                throw new NotFoundException($errorMessage);
            }
        }catch (OutOfRangeException $e){
            throw new NotFoundException($requestId, $e);
        }
    }
}
