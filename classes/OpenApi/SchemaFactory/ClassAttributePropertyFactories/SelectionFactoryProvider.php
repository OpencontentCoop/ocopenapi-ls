<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

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
            "type" => "array",
            "nullable" => true,
            "enum" => $this->values,
            "description" => $this->getPropertyDescription(),
        );

        return $schema;
    }

    public function serializeValue(Content $content, $locale)
    {
        $data = (array)$this->getContent($content, $locale);
        $data = array_filter($data);

        return $data;
    }
}