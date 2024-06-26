<?php

namespace Opencontent\OpenApi\EndpointFactory;

use erasys\OpenApi\Spec\v3\PathItem;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\OperationFactory;

trait ChildEndpointFactoryTrait
{
    /**
     * @var EndpointFactory
     */
    protected $parentEndpoint;

    /**
     * @var OperationFactory
     */
    protected $parentOperationFactory;

    public function getId()
    {
        if ($this->id === null){
            $this->id = $this->getParentEndpointFactory()->getId() . $this->generateId();
        }
        return $this->id;
    }

    /**
     * @param OperationFactory $parentOperationFactory
     * @return ChildEndpointFactoryTrait
     */
    public function setParentOperationFactory($parentOperationFactory)
    {
        $this->parentOperationFactory = $parentOperationFactory;
        return $this;
    }

    /**
     * @return EndpointFactory
     */
    public function getParentEndpointFactory()
    {
        return $this->parentEndpoint;
    }

    /**
     * @param NodeClassesEndpointFactory $parentEndpoint
     * @return ChildEndpointFactoryTrait
     */
    public function setParentEndpointFactory($parentEndpoint)
    {
        $this->parentEndpoint = $parentEndpoint;
        return $this;
    }

    /**
     * @return OperationFactory
     */
    public function getParentOperationFactory()
    {
        return $this->parentOperationFactory;
    }

    public function generatePathItem()
    {
        $item = new PathItem();
        foreach ($this->getOperationFactoryCollection()->getOperationFactories() as $operation){
            $operationDefinition = $operation->generateOperation();
            $parameters = [];
            foreach ($this->getParentOperationFactory()->generateOperation()->parameters as $parameter){
                $parameters[$parameter->name] = $parameter;
            }
            foreach ((array)$operationDefinition->parameters as $parameter){
                $parameters[$parameter->name] = $parameter;
            }
            $operationDefinition->parameters = array_values($parameters);
            $item->{$operation->getMethod()} = $operationDefinition;
        }

        return $item;
    }

    public function jsonSerialize()
    {
        $data = parent::jsonSerialize();
        $data['type'] = get_class($this);
        $data['parentEndpoint'] = $this->getParentEndpointFactory()->getId();
        $data['parentOperationFactory'] = $this->getParentOperationFactory()->getId();

        return $data;
    }
}