<?php

namespace Opencontent\OpenApi\SchemaFactory;

use Opencontent\OpenApi\SchemaFactory;

abstract class AbstractClassAttributeSchemaFactory extends SchemaFactory implements ClassAttributeSchemaFactoryInterface
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

    public function serialize()
    {
        return serialize([
            'classAttributeId' => $this->getClassAttributeId(),
            'name' => $this->name,
        ]);
    }
}