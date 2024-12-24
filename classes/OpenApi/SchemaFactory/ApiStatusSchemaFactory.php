<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Exception;
use Opencontent\OpenApi\SchemaFactory;

class ApiStatusSchemaFactory extends SchemaFactory
{
    protected $name = 'ApiStatus';

    public function generateSchema()
    {
        $schema = new OA\Schema();
        $schema->title = $this->name;
        $schema->type = 'object';
        $schema->properties = [
            'status' => $this->generateSchemaProperty([
                'type' => 'string',
                'description' => 'Status message',
                'enum' => ['ok']
            ]),
        ];

        return $schema;
    }

    public function generateRequestBody()
    {
        return new OA\RequestBody(['application/json' => new OA\MediaType([
            'schema' => new OA\Reference('#/components/schemas/' . $this->name)
        ])]);
    }

    public function serialize()
    {
        return serialize([
            'name' => $this->name,
        ]);
    }
}