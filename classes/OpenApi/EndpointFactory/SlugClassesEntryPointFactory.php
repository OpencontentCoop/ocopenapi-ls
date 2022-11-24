<?php

namespace Opencontent\OpenApi\EndpointFactory;

use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\SchemaFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaSerializer;

class SlugClassesEntryPointFactory extends NodeClassesEndpointFactory
{
    protected $nodeParameter;

    protected $serializer;

    protected $slugIdMap;

    public function __construct($nodeParameter, array $classIdentifierList, array $slugIdMap, ContentClassSchemaSerializer $serializer)
    {
        $this->nodeParameter = $nodeParameter;
        $this->serializer = $serializer;
        $this->slugIdMap = $slugIdMap;
        parent::__construct(null, $classIdentifierList);
    }

    public function getNodeId()
    {
        foreach ($this->getOperationFactoryCollection()->getOperationFactories() as $item) {
            if ($item->getCurrentRequest()) {
                $nodeSlug = $item->getCurrentRequest()->variables[$this->nodeParameter];
                if (isset($this->slugIdMap[$nodeSlug])){
                    $this->nodeId = $this->slugIdMap[$nodeSlug];
                }else{
                    throw new NotFoundException($nodeSlug);
                }

            }
        }
        return (int)$this->nodeId;
    }

    protected function getCurrentSlug()
    {
        foreach ($this->getOperationFactoryCollection()->getOperationFactories() as $item) {
            if ($item->getCurrentRequest()) {
                return $item->getCurrentRequest()->variables[$this->nodeParameter];
            }
        }

        return 'default';
    }

    protected function generateId()
    {
        return 'SlugClasses' . $this->nodeParameter . 'Classes' . implode('-', $this->getClassIdentifierList());
    }

    public function provideSchemaFactories($refresh = false)
    {
        if ($refresh) {
            $this->schemaFactories = null;
        }
        if ($this->schemaFactories === null) {
            $this->schemaFactories = [];
            foreach ($this->getClassIdentifierList() as $class) {
                $factory = new SchemaFactory\ContentClassSchemaFactory($class);
                if ($this->serializer){
                    $factory->setSerializer($this->serializer);
                }
                $this->schemaFactories[] = $factory;
            }
        }

        return $this->schemaFactories;
    }

    public function replacePrefix($subject)
    {
        if (is_string($subject)) {
            $subject = str_replace('{' . $this->nodeParameter . '}', $this->getCurrentSlug(), $subject);
        }
        return $subject;
    }
}
