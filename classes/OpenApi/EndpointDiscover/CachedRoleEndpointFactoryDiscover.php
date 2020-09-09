<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\CacheCleanable;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\EndpointFactoryProviderInterface;

class CachedRoleEndpointFactoryDiscover extends EndpointFactoryProvider implements CacheCleanable
{
    /**
     * @var EndpointFactory[]
     */
    private $endpoints;

    private $realProvider;

    private $forceRegenerate;

    public function __construct(EndpointFactoryProviderInterface $realProvider, $forceRegenerate = false)
    {
        $this->realProvider = $realProvider;
        $this->forceRegenerate = $forceRegenerate;
    }

    public function getEndpointFactoryCollection()
    {
        if ($this->endpoints === null){
            $this->endpoints = $this->getFromCache()->getEndpointFactoryCollection();
        }
        return $this->endpoints;
    }

    public function clearCache()
    {
        $cacheFilePath = $this->cacheFilePath();
        $cacheFile = \eZClusterFileHandler::instance($cacheFilePath);
        $cacheFile->delete();
        $cacheFile->purge();
    }

    /**
     * @return EndpointFactoryProviderInterface
     */
    private function getFromCache()
    {
        $cacheFilePath = $this->cacheFilePath();
        $cacheFile = \eZClusterFileHandler::instance($cacheFilePath);
        $realProvider = $this->realProvider;

        if ($this->forceRegenerate){
            $this->clearCache();
        }

        return unserialize($cacheFile->processCache(
            function ($file) {
                $content = include($file);
                return $content;
            },
            function () use ($realProvider) {
                $realProvider->getEndpointFactoryCollection();
                $content = serialize($realProvider);

                return array(
                    'content' => $content,
                    'scope' => 'cache',
                    'datatype' => 'php',
                    'store' => true
                );
            },
            null,
            null
        ));
    }

    private function cacheFilePath()
    {
        return \eZSys::cacheDirectory() . '/openpa/openapi/' . str_replace('\\', '', get_class($this->realProvider)) . '.cache';
    }
}