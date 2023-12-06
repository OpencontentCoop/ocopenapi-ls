<?php

namespace Opencontent\OpenApi;

use Opencontent\OpenApi\EndpointDiscover\ChainEndpointFactoryDiscover;
use Opencontent\OpenApi\EndpointDiscover\EmptyEndpointFactory;
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
        if (!$endpointProvider && $this->endpointProvider === null) {
            $providers = [];
            if (\eZINI::instance('ocopenapi.ini')->hasVariable('EndpointFactoryProvider', 'ProviderList')) {
                $providerList = \eZINI::instance('ocopenapi.ini')->variable('EndpointFactoryProvider', 'ProviderList');
                foreach ($providerList as $providerClass){
                    if (class_exists($providerClass)){
                        $provider = new $providerClass();
                        if ($this->forceRegenerateCache && $provider instanceof CacheCleanable){
                            $provider->clearCache();
                        }
                        if ($provider instanceof EndpointFactoryProviderInterface){
                            $providers[] = $provider;
                        }
                    }
                }
            }
            if (empty($providers)){
                \eZDebug::writeNotice("Openapi provider list is empty", __METHOD__);
                $endpointProvider = new EmptyEndpointFactory();
            }else {
                $endpointProvider = new ChainEndpointFactoryDiscover($providers);
            }
        }
        $this->endpointProvider = $endpointProvider;
    }

    public static function clearCache()
    {
        $loader = self::instance();
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
     * @return EndpointFactoryProviderInterface
     */
    public function getEndpointProvider()
    {
        return $this->endpointProvider;
    }

    /**
     * @return SchemaBuilderInterface
     */
    public function getSchemaBuilder($filters = [])
    {
        if (isset($filters['section'])){
            $settings = $this->getSettingsProvider()->provideSettings();
            if ($settings->hasDocumentationSection($filters['section'])){
                $section = $settings->getDocumentationSection($filters['section']);
                $tags = $section['tags'] ?? false;
                $readOnly = $section['read_only'] ?? false;
                if ($tags) {
                    return new TagFilteredSchemaBuilder($this->schemaBuilder, $tags, $section['title'], $readOnly === 'true');
                }
            }
        }elseif (isset($filters['tag'])){
            $tags =  (array)$filters['tag'];
            if (!empty($tags)){
                return new TagFilteredSchemaBuilder($this->schemaBuilder, $tags);
            }
        }
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