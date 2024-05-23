<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\CacheCleanable;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\EndpointFactoryProviderInterface;

class ChainEndpointFactoryDiscover extends EndpointFactoryProvider implements CacheCleanable
{
    private $providers;

    private $endpoints;

    /**
     * ChainEndpointFactoryDiscover constructor.
     * @param EndpointFactoryProviderInterface[] $providers
     */
    public function __construct(array $providers)
    {
        $this->providers = $providers;
    }

    public function getEndpointFactoryCollection()
    {
        if ($this->endpoints === null) {
            $this->endpoints = [];
            foreach ($this->providers as $provider){
                $this->endpoints = array_merge($this->endpoints, $provider->getEndpointFactoryCollection()->getEndpoints());
            }
        }

        return new EndpointFactoryCollection($this->endpoints);
    }

    public function clearCache()
    {
        foreach ($this->providers as $provider){
            if ($provider instanceof CacheCleanable){
                $provider->clearCache();
            }
        }
    }

    public function getProviders(): array
    {
        return $this->providers;
    }
}