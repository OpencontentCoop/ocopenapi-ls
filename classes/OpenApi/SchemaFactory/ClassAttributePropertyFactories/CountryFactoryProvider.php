<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

class CountryFactoryProvider extends ContentClassAttributePropertyFactory
{
    private $values;

    public function __construct(\eZContentClass $class, \eZContentClassAttribute $attribute)
    {
        parent::__construct($class, $attribute);

        $countries = \eZCountryType::fetchCountryList();
        $this->values = array();
        foreach ($countries as $country) {
            $this->values[$country['Alpha2']] = $country['Name'];
        }
    }

    public function provideProperties()
    {
        $schema = array(
            "enum" => array_keys($this->values),
            "description" => $this->getPropertyDescription(),
        );

        $classContent = $this->attribute->content();
        $default = $classContent['default_countries'];
        if (!empty( $default )) {
            $schema['default'] = array_keys($default);
        }

        return $schema;
    }

}