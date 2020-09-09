<?php

namespace Opencontent\OpenApi\SchemaBuilder;

interface SettingsProviderInterface
{
    /**
     * @return Settings
     */
    public function provideSettings();
}