<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;

abstract class SchemaFactory implements \JsonSerializable, \Serializable
{
    use SchemaBuilderToolsTrait;

    protected $name;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    public function getItemIdLabel()
    {
        return lcfirst($this->name) . 'Id';
    }

    public function getItemIdDescription()
    {
        return $this->fromCamelCase($this->getItemIdLabel(), ' ');
    }

    /**
     * @param mixed $name
     * @return SchemaFactory
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function __toString()
    {
        return (string)$this->name;
    }

    public function jsonSerialize()
    {
        $data = get_object_vars($this);
        $data['type'] = get_class($this);

        return $data;
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $key => $value){
            $this->{$key} = $value;
        }
    }

    /**
     * @return OA\Schema
     */
    abstract public function generateSchema();

    /**
     * @return OA\RequestBody
     */
    abstract public function generateRequestBody();

    /**
     * @param $source
     * @param $locale
     * @return mixed
     */
    public function serializeValue($source, $locale)
    {
        return $source;
    }
}