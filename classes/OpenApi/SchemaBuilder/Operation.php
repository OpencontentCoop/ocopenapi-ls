<?php

namespace Opencontent\OpenApi\SchemaBuilder;

use erasys\OpenApi\Spec\v3 as OA;
use stdClass;

class Operation extends OA\Operation
{
    public function toArray()
    {
        $array = parent::toArray();
        $responses = $array['responses'];
        $array['responses'] = new stdClass();
        foreach ($responses as $status => $response) {
            $array['responses']->{$status} = $response;
        }

        return $array;
    }
}