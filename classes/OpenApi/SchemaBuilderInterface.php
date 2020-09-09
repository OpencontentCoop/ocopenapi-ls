<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaBuilder\Settings;

interface SchemaBuilderInterface
{
    /**
     * @return OA\Document|\JsonSerializable
     */
    public function build();

    /**
     * @return Settings
     */
    public function getSettings();
}