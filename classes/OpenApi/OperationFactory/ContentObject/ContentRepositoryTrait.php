<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\EndpointFactory;
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
            $currentEnvironment = new \OpenApiEnvironmentSettings($endpointFactory->getNodeId(), $this->getSchemaFactories());
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
            $currentEnvironment = new \OpenApiEnvironmentSettings($endpointFactory->getNodeId(), $this->getSchemaFactories());
            $this->searchRepository->setEnvironment($currentEnvironment);
            $currentEnvironment->__set('request', $this->getCurrentRequest());
        }

        return $this->searchRepository;
    }
}