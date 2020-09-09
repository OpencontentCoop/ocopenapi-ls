<?php

namespace Opencontent\OpenApi;

use Opencontent\OpenApi\SchemaBuilder\Settings;

class CachedSchemaBuilder implements SchemaBuilderInterface, CacheCleanable
{
    private $realBuilder;

    private $forceRegenerate;

    public function __construct(SchemaBuilderInterface $realBuilder, $forceRegenerate = false)
    {
        $this->realBuilder = $realBuilder;
        $this->forceRegenerate = $forceRegenerate;
    }

    public function build()
    {
        $cacheFilePath = $this->cacheFilePath();
        $cacheFile = \eZClusterFileHandler::instance($cacheFilePath);
        $realBuilder = $this->realBuilder;

        if ($this->forceRegenerate){
            $this->clearCache();
        }

        $data = $cacheFile->processCache(
            function ($file) {
                $content = include($file);
                return $content;
            },
            function () use ($realBuilder) {
                $content = $realBuilder->build();

                return array(
                    'content' => json_encode($content),
                    'scope' => 'cache',
                    'datatype' => 'php',
                    'store' => true
                );
            },
            null,
            null
        );

        return json_decode($data, true);
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->realBuilder->getSettings();
    }

    public function clearCache()
    {
        $cacheFilePath = $this->cacheFilePath();
        $cacheFile = \eZClusterFileHandler::instance($cacheFilePath);
        $cacheFile->delete();
        $cacheFile->purge();
    }

    private function cacheFilePath()
    {
        return \eZSys::cacheDirectory() . '/openpa/openapi/' . str_replace('\\', '', get_class($this->realBuilder)) . '.cache';
    }
}