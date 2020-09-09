<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3\PathItem;

abstract class EndpointFactory implements \JsonSerializable, \Serializable
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var boolean
     */
    protected $enabled;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var OperationFactoryCollection
     */
    protected $operationFactoryCollection;

    /**
     * @var string[]
     */
    protected $tags;

    protected $baseUri;

    /**
     * @return mixed
     */
    public function getId()
    {
        if ($this->id === null){
            $this->id = $this->generateId();
        }
        return $this->id;
    }

    protected function generateId()
    {
        return $this->getPath();
    }

    /**
     * @param mixed $id
     * @return EndpointFactory
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return EndpointFactory
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return EndpointFactory
     */
    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return OperationFactoryCollection
     */
    public function getOperationFactoryCollection()
    {
        return $this->operationFactoryCollection;
    }

    /**
     * @param OperationFactoryCollection $operations
     * @return EndpointFactory
     */
    public function setOperationFactoryCollection($operations)
    {
        $this->operationFactoryCollection = $operations
            ->setTags($this->getTags())
            ->setOperationsId(function (OperationFactory $operationFactory){
                return $this->getId() . '#' . $operationFactory->getName();
            })
            ->setSchemaFactories($this->provideSchemaFactories());
        return $this;
    }

    public function hasOperationMethod($method)
    {
        return $this->operationFactoryCollection->hasOperationMethod($method);
    }

    public function getOperationByMethod($method)
    {
        return $this->operationFactoryCollection->getOperationByMethod($method);
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return (array)$this->tags;
    }

    /**
     * @param string[] $tags
     * @return EndpointFactory
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        if ($this->operationFactoryCollection instanceof OperationFactoryCollection){
            $this->getOperationFactoryCollection()->setTags($tags);
        }
        return $this;
    }

    /**
     * @param string $tag
     * @return EndpointFactory
     */
    public function addTag($tag)
    {
        $this->tags[] = $tag;
        $this->tags = array_unique($this->tags);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getBaseUri()
    {
        return $this->baseUri;
    }

    /**
     * @param mixed $baseUri
     * @return EndpointFactory
     */
    public function setBaseUri($baseUri)
    {
        $this->baseUri = $baseUri;
        return $this;
    }

    public function __toString()
    {
        return (string)$this->path;
    }

    public function toArray()
    {
        $data['path'] = $this->getPath();
        $data['enabled'] = (int)$this->isEnabled();
        $data['operations'] = $this->getOperationFactoryCollection()->toArray();
        $data['type'] = get_class($this);

        return $data;
    }

    public function jsonSerialize()
    {
        $data = get_object_vars($this);
        $data['type'] = get_class($this);

        return $data;
    }

    public function generatePathItem()
    {
        $item = new PathItem();
        foreach ($this->getOperationFactoryCollection()->getOperationFactories() as $operation){
            $item->{$operation->getMethod()} = $operation->generateOperation();
        }
        return $item;
    }

    public function serialize()
    {
        return serialize([
            'id' => $this->getId(),
            'enabled' => $this->isEnabled(),
            'path' => $this->getPath(),
            'operationFactoryCollection' => $this->operationFactoryCollection,
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $key => $value){
            $this->{$key} = $value;
        }
    }

    /**
     * @return SchemaFactory[]
     */
    abstract public function provideSchemaFactories();
}