<?php

namespace Opencontent\OpenApi;

class EndpointFactoryCollection implements \ArrayAccess, \Iterator, \JsonSerializable
{
    /**
     * @var EndpointFactory[]
     */
    private $endpoints = [];

    private $position = 0;

    public function __construct($endpoints = [])
    {
        $this->endpoints = $endpoints;
        $this->position = 0;
    }

    public function offsetExists($offset)
    {
        return isset($this->endpoints[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->endpoints[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->endpoints[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->endpoints[$offset]);
    }

    public function current()
    {
        return $this->endpoints[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->endpoints[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @param callable $callback
     * @return EndpointFactory|false
     */
    public function findOneByCallback(callable $callback)
    {
        foreach ($this->endpoints as $endpoint){
            if ($callback($endpoint)){
                return $endpoint;
            }
        }

        return false;
    }

    public function jsonSerialize()
    {
        return $this->endpoints;
    }


}