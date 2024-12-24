<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;

class RelationFactoryProvider extends ContentClassAttributePropertyFactory
{
    const MODE_LIST_BROWSE = 0;

    const MODE_LIST_DROP_DOWN = 1;

    protected $selectionType;

    protected $defaultPlacement;

    public function __construct(\eZContentClass $class, \eZContentClassAttribute $attribute)
    {
        parent::__construct($class, $attribute);

        /** @var array $classContent */
        $classContent = $this->attribute->content();
        $this->selectionType = (int)$classContent['selection_type'];
        $this->defaultPlacement = $classContent['default_selection_node'] ? $classContent['default_selection_node'] : null;
    }

    public function provideProperties()
    {
        return [
            "type" => "object",
            "nullable" => true,
            "properties" => [
                "id" => [
                    "description" => 'Content id',
                    "type" => "string"
                ],
                "name" => [
                    "description" => "Name",
                    "type" => "string",
                    "readOnly" => true,
                ],
            ],
            "required" => ['id']
        ];
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (isset($payload[$this->providePropertyIdentifier()])){
            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                $payload[$this->providePropertyIdentifier()]['id']
            );
        }
    }

}