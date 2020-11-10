<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Logger;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\SchemaFactory;

class NodeClassesEndpointFactory extends EndpointFactory
{
    /**
     * @var integer
     */
    protected $nodeId;

    protected $classIdentifierList;
    /**
     * @var string
     */
    protected $roleName;

    private $schemaFactories;

    public $originalPath;

    public function __construct($nodeId, array $classIdentifierList)
    {
        $this->nodeId = (int)$nodeId;
        $this->classIdentifierList = $classIdentifierList;
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
     * @param mixed $classIdentifierList
     * @return NodeClassesEndpointFactory
     */
    public function appendClassIdentifierList($classIdentifierList)
    {
        $classIdentifierList = array_unique(array_merge($this->classIdentifierList, $classIdentifierList));

        return $this->setClassIdentifierList($classIdentifierList);
    }

    /**
     * @param mixed $classIdentifierList
     * @return NodeClassesEndpointFactory
     */
    private function setClassIdentifierList(array $classIdentifierList)
    {
        foreach ($classIdentifierList as $index => $classIdentifier) {
            if (!\eZContentClass::classIDByIdentifier($classIdentifier)) {
                Logger::instance()->error("Class not found", ['identifier' => $classIdentifier, 'method' => __METHOD__]);
                unset($classIdentifierList[$index]);
            }
        }
        $this->classIdentifierList = $classIdentifierList;
        $this->provideSchemaFactories(true);
        $this->id = $this->generateId();
        if ($this->operationFactoryCollection instanceof OperationFactoryCollection) {
            $this->operationFactoryCollection
                ->setOperationsId(function ($operationFactory) {
                    return $operationFactory->getName() . $this->getId();
                })
                ->setSchemaFactories($this->provideSchemaFactories());
        }
        $this->getPath();

        return $this;
    }

    /**
     * @param bool $refresh
     * @return SchemaFactory[]
     */
    public function provideSchemaFactories($refresh = false)
    {
        if ($refresh) {
            $this->schemaFactories = null;
        }
        if ($this->schemaFactories === null) {
            $this->schemaFactories = [];
            foreach ($this->getClassIdentifierList() as $class) {
                $this->schemaFactories[] = new SchemaFactory\ContentClassSchemaFactory($class);
            }
        }

        return $this->schemaFactories;
    }

    /**
     * @return mixed
     */
    public function getClassIdentifierList()
    {
        return $this->classIdentifierList;
    }

    protected function generateId()
    {
        return 'Node' . $this->getNodeId() . 'Classes' . implode('-', $this->getClassIdentifierList());
    }

    /**
     * @return int
     */
    public function getNodeId()
    {
        return (int)$this->nodeId;
    }

    public function serialize()
    {
        return serialize([
            'id' => $this->getId(),
            'enabled' => $this->isEnabled(),
            'path' => $this->getPath(),
            'originalPath' => $this->originalPath,
            'nodeId' => $this->getNodeId(),
            'classIdentifierList' => $this->getClassIdentifierList(),
            'operationFactoryCollection' => $this->operationFactoryCollection,
        ]);
    }

    public function setPath($path)
    {
        if ($this->originalPath === null){
            $this->originalPath = $path;
        }
        return parent::setPath($path);
    }

    public function getPath()
    {
        $this->path = $this->originalPath;
        $this->provideSchemaFactories();
        if (count($this->schemaFactories) === 1) {
            $this->path = str_replace('{id}', '{' . $this->schemaFactories[0]->getItemIdLabel() . '}', $this->originalPath);
        }

        return $this->path;
    }
}