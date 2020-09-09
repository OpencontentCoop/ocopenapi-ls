<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\SchemaFactory;
use Opencontent\OpenApi\SchemaFactory\MatrixSchemaFactory;

class MatrixEndpointFactory extends EndpointFactory implements ChildEndpointFactoryInterface
{
    use ChildEndpointFactoryTrait;

    protected $classAttributeId;

    public function __construct($classAttributeId)
    {
        $this->classAttributeId = $classAttributeId;
    }

    /**
     * @return integer
     */
    public function getClassAttributeId()
    {
        return $this->classAttributeId;
    }

    protected function generateId()
    {
        return 'Matrix::' .  $this->getClassAttributeId();
    }

    /**
     * @return SchemaFactory[]
     */
    public function provideSchemaFactories()
    {
        return [new MatrixSchemaFactory($this->classAttributeId)];
    }

    public function getPath()
    {
        $schemaFactories = $this->provideSchemaFactories();
        if (count($schemaFactories) === 1){
            $this->path = str_replace('{matrixItemId}', '{' . $schemaFactories[0]->getItemIdLabel() . '}', $this->path);
        }

        return $this->path;
    }

    public function serialize()
    {
        return serialize([
            'id' => $this->getId(),
            'enabled' => $this->isEnabled(),
            'path' => $this->getPath(),
            'classAttributeId' => $this->getClassAttributeId(),
            'operationFactoryCollection' => $this->operationFactoryCollection,
            'parentEndpoint' => $this->parentEndpoint,
            'parentOperationFactory' => $this->parentOperationFactory,
        ]);
    }
}