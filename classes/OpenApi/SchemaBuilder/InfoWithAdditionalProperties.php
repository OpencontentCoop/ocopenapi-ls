<?php

namespace Opencontent\OpenApi\SchemaBuilder;

use erasys\OpenApi\Spec\v3 as OA;

class InfoWithAdditionalProperties extends OA\Info
{
    public $xApiId;
    public $xAudience;

    public $xsummary;
}