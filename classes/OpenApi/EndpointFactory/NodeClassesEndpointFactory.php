<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Logger;
use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\SchemaFactory;

class NodeClassesEndpointFactory extends EndpointFactory
{
    /**
     * @var integer
     */
    protected $nodeId;

    protected $classIdentifierList;

    private $schemaFactories;

    /**
     * @var string
     */
    protected $roleName;

    public function __construct($nodeId, array $classIdentifierList)
    {
        $this->nodeId = (int)$nodeId;
        $this->classIdentifierList = $classIdentifierList;
    }

    /**
     * @return int
     */
    public function getNodeId()
    {
        return (int)$this->nodeId;
    }

    /**
     * @return string
     */
    public function getRoleName()
    {
        return $this->roleName;
    }

    /**
     * @param string $roleName
     * @return EndpointFactory
     */
    public function setRoleName($roleName)
    {
        $this->roleName = $roleName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassIdentifierList()
    {
        return $this->classIdentifierList;
    }

    /**
     * @param mixed $classIdentifierList
     * @return NodeClassesEndpointFactory
     */
    public function setClassIdentifierList($classIdentifierList)
    {
        foreach ($classIdentifierList as $index => $classIdentifier){
            if (!\eZContentClass::classIDByIdentifier($classIdentifier)){
                Logger::instance()->error("Class not found", ['identifier' => $classIdentifier, 'method' => __METHOD__]);
                unset($classIdentifierList[$index]);
            }
        }
        $this->classIdentifierList = $classIdentifierList;
        if ($this->operationFactoryCollection instanceof OperationFactoryCollection){
            $this->operationFactoryCollection->setSchemaFactories($this->provideSchemaFactories());
        }
        return $this;
    }

    /**
     * @param mixed $classIdentifierList
     * @return NodeClassesEndpointFactory
     */
    public function appendClassIdentifierList($classIdentifierList)
    {
        $classIdentifierList = array_unique(array_merge($this->classIdentifierList, $classIdentifierList));

        return $this->setClassIdentifierList($classIdentifierList);
    }

    /**
     * @return SchemaFactory[]
     */
    public function provideSchemaFactories()
    {
        if ($this->schemaFactories === null) {
            $this->schemaFactories = [];
            foreach ($this->getClassIdentifierList() as $class) {
                $this->schemaFactories[] = new SchemaFactory\ContentClassSchemaFactory($class);
            }
        }

        return $this->schemaFactories;
    }

    public function getPath()
    {
        $this->provideSchemaFactories();
        if (count($this->schemaFactories) === 1){
            $this->path = str_replace('{id}', '{' . $this->schemaFactories[0]->getItemIdLabel() . '}', $this->path);
        }

        return $this->path;
    }


    protected function generateId()
    {
        return 'NodeClasses::' . $this->getNodeId() . '::' . implode('-', $this->getClassIdentifierList());
    }

    public function serialize()
    {
        return serialize([
            'id' => $this->getId(),
            'enabled' => $this->isEnabled(),
            'path' => $this->getPath(),
            'nodeId' => $this->getNodeId(),
            'classIdentifierList' => $this->getClassIdentifierList(),
            'operationFactoryCollection' => $this->operationFactoryCollection,
        ]);
    }
}