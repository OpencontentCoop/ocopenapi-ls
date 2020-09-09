<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class SelectionFactoryProvider extends ContentClassAttributePropertyFactory
{
    private $values;

    public function __construct(\eZContentClass $class, \eZContentClassAttribute $attribute)
    {
        parent::__construct($class, $attribute);

        $classContent = $this->attribute->content();
        $this->values = array();
        foreach ($classContent['options'] as $option) {
            $this->values[] = $option['name'];
        }
    }

    public function provideProperties()
    {
        $schema = array(
            "type" => "string",
            "enum" => $this->values,
            "description" => $this->getPropertyDescription(),
        );

        return $schema;
    }

}