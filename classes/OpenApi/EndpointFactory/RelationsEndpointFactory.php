<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\SchemaFactory;
use Opencontent\OpenApi\SchemaFactory\RelationsSchemaFactory;

class RelationsEndpointFactory extends EndpointFactory implements ChildEndpointFactoryInterface
{
    use ChildEndpointFactoryTrait;

    protected $classAttributeId;

    /**
     * @var NodeClassesEndpointFactory
     */
    protected $relatedEndpoint;

    public function __construct($classAttributeId)
    {
        $this->classAttributeId = $classAttributeId;
    }

    public function appendClassAttributeId($classAttributeId)
    {
        $this->classAttributeId .= ',' . $classAttributeId;
    }

    public function hasClassAttributeId($classAttributeId)
    {
        return in_array($classAttributeId, explode(',', $this->classAttributeId));
    }

    /**
     * @return NodeClassesEndpointFactory
     */
    public function getRelatedEndpoint()
    {
        return $this->relatedEndpoint;
    }

    /**
     * @param NodeClassesEndpointFactory $relatedEndpoint
     */
    public function setRelatedEndpoint($relatedEndpoint)
    {
        $this->relatedEndpoint = $relatedEndpoint;
    }

    /**
     * @return boolean
     */
    public function hasRelatedEndpoint()
    {
        return $this->relatedEndpoint instanceof NodeClassesEndpointFactory;
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
            'relatedEndpoint' => $this->relatedEndpoint
        ]);
    }

    public function getPath()
    {
        $schemaFactories = $this->provideSchemaFactories();
        $this->path = str_replace('{relatedItemId}', '{' . $schemaFactories[0]->getItemIdLabel() . '}', $this->path);

        return $this->path;
    }

    /**
     * @return SchemaFactory[]
     */
    public function provideSchemaFactories()
    {
        $schemas = [];
        foreach (explode(',', $this->classAttributeId) as $classAttributeId){
            $schemas[] = new RelationsSchemaFactory($classAttributeId);
        }

        return $schemas;
    }

    protected function generateId()
    {
        return 'Relations' . $this->getClassAttributeId();
    }

    /**
     * @return integer
     */
    public function getClassAttributeId()
    {
        return $this->classAttributeId;
    }
}