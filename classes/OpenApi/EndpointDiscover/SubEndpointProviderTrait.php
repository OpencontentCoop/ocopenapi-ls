<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use OCMultiBinaryType;
use Opencontent\OpenApi\Logger;
use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaSerializer;
use eZContentClass;
use eZContentClassAttribute;
use eZContentObjectTreeNode;
use eZINI;
use eZObjectRelationListType;

trait SubEndpointProviderTrait
{

    /**
     * @var EndpointFactory\NodeClassesEndpointFactory[]
     */
    private $miscellanea;

    protected function createSubEndpoints(
        EndpointFactory\NodeClassesEndpointFactory $endpoint,
        OperationFactory\ContentObject\ReadOperationFactory $operation
    ) {
        foreach ($this->createMatrixSubEndpoints($endpoint, $operation) as $subPath => $subEndpoint) {
            $this->endpoints[$subPath] = $subEndpoint;
        }

        foreach ($this->createRelationsSubEndpoints($endpoint, $operation) as $subPath => $subEndpoint) {
            $this->endpoints[$subPath] = $subEndpoint;
        }

        foreach ($this->createMultiBinarySubEndpoints($endpoint, $operation) as $subPath => $subEndpoint) {
            $this->endpoints[$subPath] = $subEndpoint;
        }
    }

    private function createMatrixSubEndpoints($endpoint, $operation): array
    {
        $log = '';
        $endpoints = Utils::createMatrixSubEndpoints($endpoint, $operation, $log);
        $this->log($log);

        return $endpoints;
    }

    private function createRelationsSubEndpoints($endpoint, $operation): array
    {
        $endpoints = [];

        foreach ($operation->getSchemaFactories() as $schema) {
            if ($schema instanceof ContentClassSchemaFactory) {
                $class = eZContentClass::fetchByIdentifier($schema->getClassIdentifier());
                /** @var eZContentClassAttribute $classAttribute */
                foreach ($class->dataMap() as $classAttribute) {
                    if ($classAttribute->attribute('data_type_string') == eZObjectRelationListType::DATA_TYPE_STRING) {
                        /** @var array $classContent */
                        $classContent = $classAttribute->content();
                        $relatedEndpoints = [];
                        if (isset($classContent['default_placement']['node_id']) && (int)$classContent['default_placement']['node_id'] > 0) {
                            $relatedEndpoints = $this->findEndpointsByNodeId($classContent['default_placement']['node_id']);

                            if (!empty($classContent['class_constraint_list'])) {
                                if (empty($relatedEndpoints)) {
                                    $relatedEndpoints = $this->createReadOnlyEndpoints(
                                        $classContent['default_placement']['node_id'],
                                        $classContent['class_constraint_list']
                                    );
                                    if (!empty($relatedEndpoints)) {
                                        foreach ($relatedEndpoints as $relatedReadOnlyEndpointPath => $relatedReadOnlyEndpoint) {
                                            $this->endpoints[$relatedReadOnlyEndpointPath] = $relatedReadOnlyEndpoint;
                                        }
                                    }
                                } else {
                                    foreach ($relatedEndpoints as $relatedEndpoint) {
                                        $this->log(
                                            "Append to node endpoint {$relatedEndpoint->getPath()} classes " .
                                            implode(', ', $classContent['class_constraint_list']
                                            )
                                        );
                                        $relatedEndpoint->appendClassIdentifierList(
                                            (array)$classContent['class_constraint_list']
                                        );
                                    }
                                }
                            }
                        } elseif (!empty($classContent['class_constraint_list'])) {
                            foreach ($classContent['class_constraint_list'] as $index => $classIdentifier) {
                                if (!eZContentClass::classIDByIdentifier($classIdentifier)) {
                                    Logger::instance()->error(
                                        "Class not found",
                                        ['identifier' => $classIdentifier, 'method' => __METHOD__]
                                    );
                                    unset($classContent['class_constraint_list'][$index]);
                                }
                            }

                            if (!empty($classContent['class_constraint_list'])) {
                                $relatedEndpoints = $this->findEndpointsByClasses(
                                    $classContent['class_constraint_list']
                                );
                                if (empty($relatedEndpoints)) {
                                    //non è configurato un nodo predefinito: aggiungo a miscellanea
                                    $relatedEndpoints = $this->appendToMiscEndpoints(
                                        $classContent['class_constraint_list']
                                    );
                                }
                            }
                        }

                        $relatedEndpoint = null;
                        if (!empty($relatedEndpoints)) {
                            $relatedEndpoint = array_pop($relatedEndpoints);
                        }

                        $identifier = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                            $class,
                            $classAttribute
                        )->providePropertyIdentifier();

                        $relationsPath = $endpoint->getPath() . '/' . $identifier;
                        $relationsPathItem = $relationsPath . '/{relatedItemId}';

                        $relationsPathEndpoint = $relationsPathEndpointItem = null;
                        if (isset($this->endpoints[$relationsPath]) && $this->endpoints[$relationsPath] instanceof EndpointFactory\RelationsEndpointFactory) {
                            $relationsPathEndpoint = $this->endpoints[$relationsPath];
                            if ($this->endpoints[$relationsPathItem] instanceof EndpointFactory\RelationsEndpointFactory) {
                                $relationsPathEndpointItem = $this->endpoints[$relationsPathItem];
                            }
                        } elseif (isset($endpoints[$relationsPath]) && $endpoints[$relationsPath] instanceof EndpointFactory\RelationsEndpointFactory) {
                            $relationsPathEndpoint = $endpoints[$relationsPath];
                            if ($endpoints[$relationsPathItem] instanceof EndpointFactory\RelationsEndpointFactory) {
                                $relationsPathEndpointItem = $endpoints[$relationsPathItem];
                            }
                        }

                        if ($relationsPathEndpoint instanceof EndpointFactory\RelationsEndpointFactory) {
                            $this->log(
                                "Append to relation endpoint $relationsPath the attribute " . $class->attribute(
                                    'identifier'
                                ) . '/' . $classAttribute->attribute('identifier')
                            );
                            $relationsPathEndpoint->appendClassAttributeId($classAttribute->attribute('id'));
                            if ($relationsPathEndpointItem instanceof EndpointFactory\RelationsEndpointFactory) {
                                $relationsPathEndpointItem->appendClassAttributeId($classAttribute->attribute('id'));
                            }
                        } else {
                            $this->log(
                                "Create relation endpoint $relationsPath for attribute " . $class->attribute(
                                    'identifier'
                                ) . '/' . $classAttribute->attribute('identifier')
                            );
                            $endpoints[$relationsPath] = (new EndpointFactory\RelationsEndpointFactory(
                                $classAttribute->attribute('id')
                            ))
                                ->setPath($relationsPath)
                                ->setTags($endpoint->getTags())
                                ->setParentEndpointFactory($endpoint)
                                ->setParentOperationFactory($operation)
                                ->setOperationFactoryCollection(
                                    (new OperationFactoryCollection([
                                        (new OperationFactory\Relations\CreateOperationFactory()),
                                        (new OperationFactory\Relations\ListOperationFactory()),
                                    ]))
                                );

                            if ($relatedEndpoint instanceof EndpointFactory\NodeClassesEndpointFactory) {
                                $endpoints[$relationsPath]->setRelatedEndpoint($relatedEndpoint);
                            }

                            $endpoints[$relationsPathItem] = (new EndpointFactory\RelationsEndpointFactory(
                                $classAttribute->attribute('id')
                            ))
                                ->setPath($relationsPathItem)
                                ->setTags($endpoint->getTags())
                                ->setParentEndpointFactory($endpoint)
                                ->setParentOperationFactory($operation)
                                ->setOperationFactoryCollection(
                                    (new OperationFactoryCollection([
                                        (new OperationFactory\Relations\ReadOperationFactory()),
                                        (new OperationFactory\Relations\UpdateOperationFactory()),
                                        (new OperationFactory\Relations\DeleteOperationFactory()),
                                    ]))
                                );

                            if ($relatedEndpoint instanceof EndpointFactory\NodeClassesEndpointFactory) {
                                $endpoints[$relationsPathItem]->setRelatedEndpoint($relatedEndpoint);
                            }
                        }
//                        }
                    }
                }
            }
        }

        return $endpoints;
    }

    /**
     * @param $nodeId
     * @return EndpointFactory\NodeClassesEndpointFactory[]
     */
    private function findEndpointsByNodeId($nodeId): array
    {
        $endpoints = [];
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint instanceof EndpointFactory\NodeClassesEndpointFactory) {
                if ($endpoint->getNodeId() == $nodeId) {
                    $endpoints[$endpoint->getPath()] = $endpoint;
                }
            }
        }

        return $endpoints;
    }

    /**
     * @param $nodeId
     * @param $classes
     * @return EndpointFactory\NodeClassesEndpointFactory[]
     */
    private function createReadOnlyEndpoints($nodeId, $classes): array
    {
        $endpoints = [];

        $node = eZContentObjectTreeNode::fetch($nodeId);
        if (!$node instanceof eZContentObjectTreeNode) {
            return $endpoints;
        }

        $path = '/' . strtolower($node->urlAlias());
        $tag = str_replace('/', '-', strtolower($node->urlAlias()));

        $this->log("Create readonly endpoint $path for classes " . implode(', ', $classes));
        $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($nodeId, $classes))
            ->setPath($path)
            ->addTag($tag)
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    (new OperationFactory\ContentObject\FilteredSearchOperationFactory()),
                ])
            );

        $path .= '/{id}';
        $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($nodeId, $classes))
            ->setPath($path)
            ->addTag($tag)
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    (new OperationFactory\ContentObject\ReadOperationFactory()),
                ])
            );

        return $endpoints;
    }

    /**
     * @param string[] $classes
     * @return EndpointFactory\NodeClassesEndpointFactory[]
     */
    private function findEndpointsByClasses($classes): array
    {
        $endpoints = [];
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint instanceof EndpointFactory\NodeClassesEndpointFactory) {
                $matchClasses = $endpoint->getClassIdentifierList();
                sort($matchClasses);
                sort($classes);
                if (implode(',', $classes) == implode(',', $matchClasses)) {
                    $endpoints[$endpoint->getPath()] = $endpoint;
                }
            }
        }

        return $endpoints;
    }

    private function appendToMiscEndpoints($classes): array
    {
        if ($this->miscellanea === null) {
            $endpoints = [];

            $node = eZContentObjectTreeNode::fetch(
                eZINI::instance('content.ini')->variable('NodeSettings', 'RootNode')
            );

            $path = '/miscellanea';
            $tag = 'miscellanea';

            $this->log("Create miscellanea readonly endpoint $path for classes " . implode(', ', $classes));
            $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($node->attribute('node_id'), $classes))
                ->setPath($path)
                ->addTag($tag)
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        (new OperationFactory\ContentObject\FilteredSearchOperationFactory()),
                    ])
                );

            $path .= '/{id}';
            $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($node->attribute('node_id'), $classes))
                ->setPath($path)
                ->addTag($tag)
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        (new OperationFactory\ContentObject\ReadOperationFactory()),
                    ])
                );

            $this->miscellanea = $endpoints;
            foreach ($this->miscellanea as $path => $endpoint) {
                $this->endpoints[$path] = $endpoint;
            }
        } else {
            foreach ($this->miscellanea as $endpoint) {
                $this->log("Append to node endpoint {$endpoint->getPath()} classes " . implode(', ', $classes));
                $endpoint->appendClassIdentifierList((array)$classes);
            }
        }

        return $this->miscellanea;
    }

    private function createMultiBinarySubEndpoints($endpoint, $operation): array
    {
        $endpoints = [];
        foreach ($operation->getSchemaFactories() as $schema) {
            if ($schema instanceof ContentClassSchemaFactory) {
                $class = eZContentClass::fetchByIdentifier($schema->getClassIdentifier());
                /** @var eZContentClassAttribute $classAttribute */
                foreach ($class->dataMap() as $classAttribute) {
                    if ($classAttribute->attribute('data_type_string') == OCMultiBinaryType::DATA_TYPE_STRING) {
                        $identifier = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                            $class,
                            $classAttribute
                        )->providePropertyIdentifier();

                        $multiBinaryPath = $endpoint->getPath() . '/' . $identifier;
                        $this->log(
                            "Create multi binary endpoint $multiBinaryPath for attribute " . $class->attribute(
                                'identifier'
                            ) . '/' . $classAttribute->attribute('identifier')
                        );
                        $endpoints[$multiBinaryPath] = (new EndpointFactory\MultiBinaryEndpointFactory(
                            $classAttribute->attribute('id')
                        ))
                            ->setPath($multiBinaryPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection(
                                (new OperationFactoryCollection([
                                    (new OperationFactory\MultiBinary\CreateOperationFactory()),
                                    (new OperationFactory\MultiBinary\ListOperationFactory()),
                                ]))
                            );

                        $multiBinaryPath = $multiBinaryPath . '/{multiBinaryFilename}';
                        $endpoints[$multiBinaryPath] = (new EndpointFactory\MultiBinaryEndpointFactory(
                            $classAttribute->attribute('id')
                        ))
                            ->setPath($multiBinaryPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection(
                                (new OperationFactoryCollection([
                                    (new OperationFactory\MultiBinary\ReadOperationFactory()),
                                    (new OperationFactory\MultiBinary\UpdateOperationFactory()),
                                    (new OperationFactory\MultiBinary\DeleteOperationFactory()),
                                ]))
                            );
                    }
                }
            }
        }
        return $endpoints;
    }
}