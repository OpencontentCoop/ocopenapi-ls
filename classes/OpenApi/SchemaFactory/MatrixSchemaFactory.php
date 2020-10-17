<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;

class MatrixSchemaFactory extends AbstractClassAttributeSchemaFactory
{
    /**
     * @return OA\Schema
     */
    public function generateSchema()
    {
        /** @var \eZMatrixDefinition $definition */
        $definition = \eZContentClassAttribute::fetch($this->classAttributeId)->attribute('content');
        $columns = $definition->attribute('columns');

        $schema = new OA\Schema();
        $schema->title = $this->name;
        $schema->type = 'object';
        $schema->properties = [];
        $schema->properties['index'] = $this->generateSchemaProperty(['type' => 'integer', 'title' => 'Item index']);
        $required = [];
        foreach ($columns as $column) {
            $schema->properties[$column['identifier']] = $this->generateSchemaProperty(['type' => 'string', 'title' => $column['name']]);
            $required[] = $column['identifier'];
        };
        $schema->required = $required;

        return $schema;
    }

    /**
     * @return OA\RequestBody
     */
    public function generateRequestBody()
    {
        $schema = $this->generateSchema();
        $schema->title = $schema->title . 'Struct';
        unset($schema->properties['index']);

        return new OA\RequestBody(['application/json' => new OA\MediaType([
            'schema' => $schema
        ])]);
    }
}