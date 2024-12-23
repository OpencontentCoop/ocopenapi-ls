<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaSerializer;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\OperationFactory;
use eZContentClass;
use eZContentClassAttribute;
use eZMatrixType;

class Utils
{
    /**
     * @param $endpoint
     * @param $operation
     * @param string $log
     * @return EndpointFactory\ChildEndpointFactoryInterface[]
     */
    public static function createMatrixSubEndpoints(
        EndpointFactory\NodeClassesEndpointFactory $endpoint,
        OperationFactory\ContentObject\ReadOperationFactory $operation,
        string &$log = null
    ): array {
        $endpoints = [];
        foreach ($operation->getSchemaFactories() as $schema) {
            if ($schema instanceof ContentClassSchemaFactory) {
                $class = eZContentClass::fetchByIdentifier($schema->getClassIdentifier());
                /** @var eZContentClassAttribute $classAttribute */
                foreach ($class->dataMap() as $classAttribute) {
                    if ($classAttribute->attribute('data_type_string') == eZMatrixType::DATA_TYPE_STRING) {
                        $propertyFactory = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                            $class,
                            $classAttribute
                        );
                        if (!$propertyFactory instanceof ContentClassAttributePropertyFactory) {
                            continue;
                        }
                        $identifier = $propertyFactory->providePropertyIdentifier();

                        $matrixPath = $endpoint->getPath() . '/' . $identifier;
                        $log .= "Create matrix endpoint $matrixPath for attribute " . $class->attribute('identifier') . '/' . $classAttribute->attribute('identifier') . PHP_EOL;
                        $endpoints[$matrixPath] = (new EndpointFactory\MatrixEndpointFactory(
                            $classAttribute->attribute('id')
                        ))
                            ->setPath($matrixPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection(
                                (new OperationFactoryCollection([
                                    (new OperationFactory\Matrix\CreateOperationFactory()),
                                    (new OperationFactory\Matrix\ListOperationFactory()),
                                ]))
                            );

                        $matrixPath = $matrixPath . '/{matrixItemId}';
                        $endpoints[$matrixPath] = (new EndpointFactory\MatrixEndpointFactory(
                            $classAttribute->attribute('id')
                        ))
                            ->setPath($matrixPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection(
                                (new OperationFactoryCollection([
                                    (new OperationFactory\Matrix\ReadOperationFactory()),
                                    (new OperationFactory\Matrix\UpdateOperationFactory()),
                                    (new OperationFactory\Matrix\DeleteOperationFactory()),
                                ]))
                            );
                    }
                }
            }
        }

        return $endpoints;
    }
}