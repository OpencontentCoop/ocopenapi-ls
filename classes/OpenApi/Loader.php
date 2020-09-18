<?php

namespace Opencontent\OpenApi;

use Opencontent\OpenApi\EndpointDiscover\CachedRoleEndpointFactoryDiscover;
use Opencontent\OpenApi\EndpointDiscover\RoleEndpointFactoryDiscover;
use Opencontent\OpenApi\SchemaBuilder\IniSettingsProvider;
use Opencontent\OpenApi\SchemaBuilder\SettingsProviderInterface;

class Loader
{
    const API_VERSION = '1.0.0 alpha';

    private static $instance;

    /**
     * @var SettingsProviderInterface
     */
    private $settingsProvider;

    /**
     * @var EndpointFactoryProviderInterface
     */
    private $endpointProvider;

    /**
     * @var SchemaBuilderInterface
     */
    private $schemaBuilder;

    private $cacheEnabled = true;

    private $forceRegenerateCache = false;

    private function __construct(
        SettingsProviderInterface $settingsProvider = null,
        EndpointFactoryProviderInterface $endpointProvider = null
    )
    {
        $this->setDefaultSettingsProvider($settingsProvider);
        $this->setDefaultEndpointProvider($endpointProvider);

        $schemaBuilder = new SchemaBuilder($this->settingsProvider, $this->endpointProvider);
        if ($this->isCacheEnabled()) {
            $this->schemaBuilder = new CachedSchemaBuilder($schemaBuilder, $this->forceRegenerateCache);
        } else {
            $this->schemaBuilder = $schemaBuilder;
        }
    }

    private function setDefaultSettingsProvider($settingsProvider)
    {
        if (!$settingsProvider) {
            $settingsProvider = new IniSettingsProvider();
        }
        $this->settingsProvider = $settingsProvider;
        $this->settingsProvider->provideSettings()->apiVersion = self::API_VERSION;
        $this->setCacheEnabled($this->settingsProvider->provideSettings()->cacheEnabled);
    }

    private function setDefaultEndpointProvider($endpointProvider)
    {
        if (!$endpointProvider) {
            $endpointProvider = new RoleEndpointFactoryDiscover();
            if ($this->isCacheEnabled()) {
                $endpointProvider = new CachedRoleEndpointFactoryDiscover($endpointProvider, $this->forceRegenerateCache);
            }
        }
        $this->endpointProvider = $endpointProvider;
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
            \eZCache::clearByID(['rest']);

            if (\eZINI::instance()->variable('ContentSettings', 'StaticCache') == 'enabled') {
                /** @var \ezpStaticCache $staticCacheHandler */
                $staticCacheHandler = \eZExtension::getHandlerClass(new \ezpExtensionOptions(['iniFile' => 'site.ini',
                    'iniSection' => 'ContentSettings',
                    'iniVariable' => 'StaticCacheHandler']));
                $staticCacheHandler->removeURL(
                    Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl . '/'
                );
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

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }

        return self::$instance;
    }

}