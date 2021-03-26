<?php

namespace Opencontent\OpenApi\OperationFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaFactory;

trait SchemaReferenceGeneratorTrait
{
    protected function generateSchemasReference()
    {
        $schemaFactories = $this->getSchemaFactories();
        if (count($schemaFactories) > 1) {
            $schemas = [];
            foreach ($schemaFactories as $schemaFactory) {
                $schemas[] = new OA\Reference('#/components/schemas/' . \OpenApiEnvironmentSettings::DISCRIMINATED_SCHEMA_PREFIX . $schemaFactory->getName());
            }
            return new OA\Schema([
                'oneOf' => $schemas,
                'discriminator' => [
                    'propertyName' => \OpenApiEnvironmentSettings::DISCRIMINATOR_PROPERTY_NAME
                ]
            ]);
        }

        if (!isset($schemaFactories[0])){
            //@todo use Logger
            \eZLog::write("Missing schema references in " . get_called_class() . "#" . $this->name, 'openapi.log');
            return '';
        }

        return new OA\Reference('#/components/schemas/' . $schemaFactories[0]->getName());
    }

    /**
     * @return SchemaFactory[]
     */
    abstract protected function getSchemaFactories();

    protected function generateRequestBodySchemasReference()
    {
        return $this->generateSchemasReference();
    }

    protected function getItemIdLabel()
    {
        $idLabel = 'id';
        if (count($this->getSchemaFactories()) == 1) {
            $idLabel = $this->getSchemaFactories()[0]->getItemIdLabel();
        }

        return $idLabel;
    }

    protected function getItemIdDescription()
    {
        $idDescription = 'Item id';
        if (count($this->getSchemaFactories()) == 1) {
            $idDescription = $this->getSchemaFactories()[0]->getItemIdDescription();
        }

        return $idDescription;
    }
}