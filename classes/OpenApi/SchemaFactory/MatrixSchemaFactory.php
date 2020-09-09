<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaFactory;

class MatrixSchemaFactory extends SchemaFactory implements ClassAttributeSchemaFactoryInterface
{
    protected $classAttributeId;

    protected $classAttribute;

    protected $class;

    public function __construct($classAttributeId)
    {
        $this->classAttributeId = $classAttributeId;
        $this->name = $this->toCamelCase(\eZContentClassAttribute::fetch($this->classAttributeId)->attribute('identifier') . '_item');
    }

    /**
     * @return integer
     */
    public function getClassAttributeId()
    {
        return $this->classAttributeId;
    }

    /**
     * @return \eZContentClassAttribute
     */
    public function getClassAttribute()
    {
        if ($this->classAttribute === null){
            $this->classAttribute = \eZContentClassAttribute::fetch($this->getClassAttributeId());
        }
        return $this->classAttribute;
    }

    /**
     * @return \eZContentClass
     */
    public function getClass()
    {
        if ($this->class === null){
            $this->class = \eZContentClass::fetch($this->getClassAttribute()->attribute('contentclass_id'));
        }
        return $this->class;
    }

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

    public function serialize()
    {
        return serialize([
            'classAttributeId' => $this->classAttributeId,
            'name' => $this->name,
        ]);
    }
}