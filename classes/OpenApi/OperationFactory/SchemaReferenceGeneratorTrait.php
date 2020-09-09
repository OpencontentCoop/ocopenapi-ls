<?php

namespace Opencontent\OpenApi\OperationFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaFactory;

trait SchemaReferenceGeneratorTrait
{
    /**
     * @return SchemaFactory[]
     */
    abstract protected function getSchemaFactories();

    protected function generateSchemasReference()
    {
        $schemaFactories = $this->getSchemaFactories();
        $items = [];
        if (count($schemaFactories) > 1) {
            $resultSchemaItems = [];
            foreach ($schemaFactories as $schemaFactory) {
                $resultSchemaItems[] = new OA\Reference('#/components/schemas/' . $schemaFactory->getName());
            }
            return new OA\Schema(['oneOf' => $resultSchemaItems]);
        }

        return new OA\Reference('#/components/schemas/' . $schemaFactories[0]->getName());
    }

    protected function generateRequestBodySchemasReference()
    {
        $schemaFactories = $this->getSchemaFactories();
        $items = [];
        if (count($schemaFactories) > 1) {
            $resultSchemaItems = [];
            foreach ($schemaFactories as $schemaFactory) {
                $schema = new OA\Schema([
                    'allOf' => [
                        new OA\Reference('#/components/schemas/' . $schemaFactory->getName()),
                        new OA\Schema([
                            'properties' => [
                                \OpenApiEnvironmentSettings::DISCRIMINATOR_PROPERTY_NAME => $this->generateSchemaProperty([
                                    'type' => 'string',
                                    'title' => 'Content type (discriminator)',
                                    'default' => $schemaFactory->getName()
                                ])
                            ],
                            'required' => ['content_type']
                        ])
                    ]
                ]);
                $resultSchemaItems[] = $schema;
            }
            return new OA\Schema(['oneOf' => $resultSchemaItems]);
        }

        return new OA\Reference('#/components/schemas/' . $schemaFactories[0]->getName());
    }

    protected function getItemIdLabel()
    {
        $idLabel = 'id';
        if (count($this->getSchemaFactories()) == 1){
            $idLabel = $this->getSchemaFactories()[0]->getItemIdLabel();
        }

        return $idLabel;
    }

    protected function getItemIdDescription()
    {
        $idDescription = 'Item id';
        if (count($this->getSchemaFactories()) == 1){
            $idDescription = $this->getSchemaFactories()[0]->getItemIdDescription();
        }

        return $idDescription;
    }
}