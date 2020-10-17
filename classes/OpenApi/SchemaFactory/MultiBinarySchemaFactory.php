<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\MultiFileFactoryProvider;

class MultiBinarySchemaFactory extends AbstractClassAttributeSchemaFactory implements ClassAttributeSchemaFactoryInterface
{
    public function __construct($classAttributeId)
    {
        parent::__construct($classAttributeId);
        $this->name = $this->toCamelCase('binary_file');
    }

    public function getItemIdLabel()
    {
        return lcfirst($this->name) . 'Filename';
    }

    public function generateSchema()
    {
        $properties = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
            $this->getClass(),
            $this->getClassAttribute()
        )->provideProperties();

        $schema = new OA\Schema();
        $schema->type = 'object';
        $schema->properties = $properties['items']['properties'];

        return $schema;
    }

    public function generateRequestBody()
    {
        $schema = $this->generateSchema();
        $schema->title = $schema->title . 'Struct';

        return new OA\RequestBody(['application/json' => new OA\MediaType([
            'schema' => $schema
        ])]);
    }

}