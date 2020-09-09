<?php

namespace Opencontent\OpenApi;

use Opencontent\OpenApi\EndpointDiscover\CachedRoleEndpointFactoryDiscover;
use Opencontent\OpenApi\EndpointDiscover\RoleEndpointFactoryDiscover;
use Opencontent\OpenApi\SchemaBuilder\IniSettingsProvider;
use Opencontent\OpenApi\SchemaBuilder\SettingsProviderInterface;

class Loader
{
    private static $instance;

    private $settingsProvider;

    private $endpointProvider;

    private $schemaBuilder;

    private $cacheEnabled = true;

    private $forceRegenerateCache = false;

    private function __construct(
        $useCache = true,
        SettingsProviderInterface $settingsProvider = null,
        EndpointFactoryProviderInterface $endpointProvider = null
    )
    {
        $this->cacheEnabled = $useCache;
        $this->setDefaults($settingsProvider, $endpointProvider);

        $schemaBuilder = new SchemaBuilder($this->settingsProvider, $this->endpointProvider);
        if ($useCache) {
            $this->schemaBuilder = new CachedSchemaBuilder($schemaBuilder, $this->forceRegenerateCache);
        } else {
            $this->schemaBuilder = $schemaBuilder;
        }
    }

    private function setDefaults($settingsProvider, $endpointProvider)
    {
        if (!$settingsProvider) {
            $settingsProvider = new IniSettingsProvider();
        }
        $this->settingsProvider = $settingsProvider;

        if (!$endpointProvider) {
            $endpointProvider = new RoleEndpointFactoryDiscover();
            if ($this->cacheEnabled) {
                $endpointProvider = new CachedRoleEndpointFactoryDiscover($endpointProvider, $this->forceRegenerateCache);
            }
        }
        $this->endpointProvider = $endpointProvider;
    }

    public static function instance(
        $useCache = true,
        SettingsProviderInterface $settingsProvider = null,
        EndpointFactoryProviderInterface $endpointProvider = null
    )
    {
        if (self::$instance === null) {
            self::$instance = new static($useCache, $settingsProvider, $endpointProvider);
        }

        return self::$instance;
    }

    public static function clearCache()
    {
        $loader = new static();
        if ($loader->isCacheEnabled()) {
            $endpointProvider = $loader->getEndpointProvider();
            if ($endpointProvider instanceof CacheCleanable) {
                $endpointProvider->clearCache();
            }
            $schemaBuilder = $loader->getSchemaBuilder();
            if ($schemaBuilder instanceof CacheCleanable) {
                $schemaBuilder->clearCache();
            }
        }
    }

    /**
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->cacheEnabled;
    }

    /**
     * @param bool $cacheEnabled
     */
    public function setCacheEnabled($cacheEnabled)
    {
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * @return RoleEndpointFactoryDiscover|EndpointFactoryProviderInterface
     */
    public function getEndpointProvider()
    {
        return $this->endpointProvider;
    }

    /**
     * @return SchemaBuilderInterface
     */
    public function getSchemaBuilder()
    {
        return $this->schemaBuilder;
    }

    /**
     * @return IniSettingsProvider|SettingsProviderInterface
     */
    public function getSettingsProvider()
    {
        return $this->settingsProvider;
    }

}