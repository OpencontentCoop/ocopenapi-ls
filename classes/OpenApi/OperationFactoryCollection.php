<?php

namespace Opencontent\OpenApi;

class OperationFactoryCollection implements \JsonSerializable
{
    /**
     * @var OperationFactory[]
     */
    private $operations = [];

    public function __construct(array $operations)
    {
        $this->setOperationFactories($operations);
    }

    /**
     * @return OperationFactory[]
     */
    public function getOperationFactories()
    {
        return array_values($this->operations);
    }

    /**
     * @param OperationFactory[] $operations
     * @return $this
     */
    public function setOperationFactories(array $operations)
    {
        foreach ($operations as $operation){
            $this->operations[$operation->getMethod()] = $operation;
        }

        return $this;
    }

    public function hasOperationMethod($method)
    {
        foreach ($this->operations as $operation){
            if ($operation->getMethod() == $method){
                return true;
            }
        }

        return false;
    }

    public function getOperationByMethod($method)
    {
        foreach ($this->operations as $operation){
            if ($operation->getMethod() == $method){
                return $operation;
            }
        }

        return false;
    }

    /**
     * @param $operationName
     * @return OperationFactory
     */
    public function getOperationFactory($operationName)
    {
        foreach ($this->operations as $operation){
            if ($operation->getName() == $operationName){
                return $operation;
            }
        }

        throw new \InvalidArgumentException("Operation $operationName not found");
    }

    /**
     * @return SchemaFactory[]
     */
    public function getSchemaFactories()
    {
        $schema = [];
        foreach ($this->operations as $operation){
            $schema = $schema + $operation->getSchemaFactories();
        }
        return $schema;
    }

    /**
     * @param SchemaFactory[] $schemas
     * @return OperationFactoryCollection
     */
    public function setSchemaFactories($schemas)
    {
        foreach ($this->operations as $operation){
            $operation->setSchemaFactories($schemas);
        }
        return $this;
    }

    /**
     * @param SchemaFactory[] $schemas
     * @return OperationFactoryCollection
     */
    public function appendSchemaFactories($schemas)
    {
        foreach ($this->operations as $operation){
            $operation->appendSchemaFactories($schemas);
        }
        return $this;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        $tags = [];
        foreach ($this->operations as $operation){
            $tags = array_merge($tags, $operation->getTags());
        }
        return array_unique($tags);
    }

    /**
     * @param string[] $tags
     * @return OperationFactoryCollection
     */
    public function setTags($tags)
    {
        foreach ($this->operations as $operation){
            $operation->setTags($tags);
        }
        return $this;
    }

    public function setOperationsId(callable $callback)
    {
        foreach ($this->operations as $operation){
            $operation->setId($callback($operation));
        }
        return $this;
    }

    public function toArray()
    {
        return $this->operations;
    }

    public function jsonSerialize()
    {
        return $this->operations;
    }

}