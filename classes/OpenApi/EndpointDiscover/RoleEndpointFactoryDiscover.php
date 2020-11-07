<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\Logger;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaSerializer;
use Opencontent\Opendata\Api\ContentSearch;

class RoleEndpointFactoryDiscover extends EndpointFactoryProvider
{
    private $cli;

    /**
     * @var EndpointFactory[]|EndpointFactory\NodeClassesEndpointFactory[]
     */
    private $endpoints;

    private $miscellanea;

    public function __construct(\eZCLI $cli = null)
    {
        $this->cli = $cli;
    }

    /**
     * @return EndpointFactoryCollection
     */
    public function getEndpointFactoryCollection()
    {
        if ($this->endpoints === null) {
            $this->endpoints = [];
            $this->discoverFromRoles();

            $sort = [];
            $this->endpoints = array_values($this->endpoints);
            foreach ($this->endpoints as $endpoint) {
                $path = $endpoint->getPath();
                $path = str_replace('{', 'aaa', $path);
                $sortKey = \eZCharTransform::instance()->transformByGroup($path, 'identifier');
                $sort[$sortKey] = $endpoint;
            }
            ksort($sort);
            $this->endpoints = array_values($sort);
        }

        return new EndpointFactoryCollection($this->endpoints);
    }

    private function discoverFromRoles()
    {
        /** @var \eZRole[] $roles */
        $roles = \eZRole::fetchList();
        foreach ($roles as $role) {
            $this->log($role->attribute('name'), 'warning');
            /** @var \eZPolicy $policy */
            foreach ($role->policyList() as $policy) {
                if ($policy->attribute('module_name') == 'content' && $policy->attribute('function_name') == 'create') {

                    /** @var \eZContentObjectTreeNode[] $subtree */
                    $subtree = [];
                    $classes = [];
                    $parentClasses = [];

                    /** @var \eZPolicyLimitation $limitation */
                    foreach ($policy->limitationList() as $limitation) {
                        if ($limitation->attribute('identifier') == 'Node') {
                            /** @var \eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $node = \eZContentObjectTreeNode::fetch($value->attribute('value'));
                                if ($node instanceof \eZContentObjectTreeNode) {
                                    $this->log('  (node) ' . $node->urlAlias());
                                    $subtree[$node->attribute('node_id')] = $node;
                                } else {
                                    $this->log('  (node) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                        if ($limitation->attribute('identifier') == 'Subtree') {
                            /** @var \eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $node = \eZContentObjectTreeNode::fetchByPath($value->attribute('value'));
                                if ($node instanceof \eZContentObjectTreeNode) {
                                    $this->log('  (subtree) ' . $node->urlAlias());
                                    $subtree[$node->attribute('node_id')] = $node;
                                } else {
                                    $this->log('  (subtree) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                        if ($limitation->attribute('identifier') == 'Class') {
                            /** @var \eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $classIdentifier = \eZContentClass::classIdentifierByID($value->attribute('value'));
                                if ($classIdentifier) {
                                    $classes[] = $classIdentifier;
                                    $this->log('  (class) ' . $classIdentifier);
                                } else {
                                    $this->log('  (class) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                        if ($limitation->attribute('identifier') == 'ParentClass') {
                            /** @var \eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $classIdentifier = \eZContentClass::classIdentifierByID($value->attribute('value'));
                                if ($classIdentifier) {
                                    $parentClasses[] = $classIdentifier;
                                    $this->log('  (parent) ' . $classIdentifier);
                                } else {
                                    $this->log('  (parent) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                    }
                    $this->log(' ');

                    $classes = array_unique($classes);


                    foreach ($subtree as $item) {

                        $pathGroup = [
                            [
                                'nodes' => [$item],
                                'path_suffix' => '',
                                'operations' => new OperationFactoryCollection([
                                    (new OperationFactory\ContentObject\CreateOperationFactory()),
                                    (new OperationFactory\ContentObject\SearchOperationFactory()),
                                ]),
                            ],
                            [
                                'nodes' => [$item],
                                'path_suffix' => '/{id}',
                                'operations' => new OperationFactoryCollection([
                                    (new OperationFactory\ContentObject\ReadOperationFactory()),
                                    (new OperationFactory\ContentObject\UpdateOperationFactory()),
                                    (new OperationFactory\ContentObject\MergePatchOperationFactory()),
                                    (new OperationFactory\ContentObject\DeleteOperationFactory()),
                                ]),
                            ],
                        ];

                        if (count($parentClasses) > 0) {

//@todo amministrazione trasparente
                            continue;

//                            $pathList = [];
//                            /** @var \eZContentObjectTreeNode[] $validNodesInPath */
//                            $validNodesInPath = $item->subTree([
//                                'ClassFilterType' => 'include',
//                                'ClassFilterArray' => $parentClasses
//                            ]);
//                            foreach ($validNodesInPath as $node) {
//                                if ($node->subTreeCount([
//                                        'ClassFilterType' => 'include',
//                                        'ClassFilterArray' => $parentClasses,
//                                        'Depth' => 1,
//                                        'DepthOperator' => 'eq'
//                                    ]) == 0) {
//                                    $nodeList[] = $node;
//                                }
//                            }
//                            $pathGroup = [
//                                [
//                                    'nodes' => $nodeList,
//                                    'path_suffix' => '',
//                                    'operations' => new OperationFactoryCollection([
//                                        (new OperationFactory\ContentObject\AddLocationOperationFactory()),
//                                        (new OperationFactory\ContentObject\RemoveLocationOperationFactory()),
//                                    ]),
//                                ],
//                            ];
                        }

                        foreach ($pathGroup as $pathGroupItem) {
                            /** @var \eZContentObjectTreeNode $node */
                            foreach ($pathGroupItem['nodes'] as $node) {
                                $path = '/' . strtolower($node->urlAlias()) . $pathGroupItem['path_suffix'];
                                $tag = str_replace('/', '-', strtolower($node->urlAlias()));
                                if (isset($this->endpoints[$path]) && $this->endpoints[$path] instanceof EndpointFactory\NodeClassesEndpointFactory) {
                                    $this->log("Append to node endpoint $path classes " . implode(', ', $classes));
                                    $this->endpoints[$path]->appendClassIdentifierList($classes);
                                } else {
                                    $this->log("Create node endpoint $path for classes " . implode(', ', $classes));
                                    $this->endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($item->attribute('node_id'), $classes))
                                        ->setPath($path)
                                        ->addTag($tag)
                                        ->setOperationFactoryCollection($pathGroupItem['operations'])
                                        ->setRoleName($role->attribute('name'));
                                }
                            }
                        }
                    }
                }
            }
            $this->log(' ');
        }

        foreach ($this->endpoints as $path => $endpoint) {
            if ($endpoint instanceof EndpointFactory\NodeClassesEndpointFactory
                && $endpoint->hasOperationMethod('get')
                && $endpoint->getOperationByMethod('get') instanceof OperationFactory\ContentObject\ReadOperationFactory) {

                $operation = $endpoint->getOperationByMethod('get');

                $this->log('...analyze ' . $endpoint->getPath(), 'warning');
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
        }
    }

    private function log($message, $level = null)
    {
        if ($this->cli instanceof \eZCLI) {
            switch ($level) {
                case 'error';
                    $this->cli->error($message);
                    break;

                case 'warning';
                    $this->cli->warning($message);
                    break;

                case 'notice';
                    $this->cli->notice($message);
                    break;

                default;
                    $this->cli->output($message);
            }

        }
    }

    private function createMultiBinarySubEndpoints($endpoint, $operation)
    {
        $endpoints = [];
        foreach ($operation->getSchemaFactories() as $schema) {
            if ($schema instanceof ContentClassSchemaFactory) {
                $class = \eZContentClass::fetchByIdentifier($schema->getClassIdentifier());
                /** @var \eZContentClassAttribute $classAttribute */
                foreach ($class->dataMap() as $classAttribute) {

                    if ($classAttribute->attribute('data_type_string') == \OCMultiBinaryType::DATA_TYPE_STRING) {

                        $identifier = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                            $class,
                            $classAttribute
                        )->providePropertyIdentifier();

                        $multiBinaryPath = $endpoint->getPath() . '/' . $identifier;
                        $this->log("Create multibinary endpoint $multiBinaryPath for attribute " . $class->attribute('identifier') . '/' . $classAttribute->attribute('identifier'));
                        $endpoints[$multiBinaryPath] = (new EndpointFactory\MultiBinaryEndpointFactory($classAttribute->attribute('id')))
                            ->setPath($multiBinaryPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection((new OperationFactoryCollection([
                                (new OperationFactory\MultiBinary\CreateOperationFactory()),
                                (new OperationFactory\MultiBinary\ListOperationFactory()),
                            ])));

                        $multiBinaryPath = $multiBinaryPath . '/{multiBinaryFilename}';
                        $endpoints[$multiBinaryPath] = (new EndpointFactory\MultiBinaryEndpointFactory($classAttribute->attribute('id')))
                            ->setPath($multiBinaryPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection((new OperationFactoryCollection([
                                (new OperationFactory\MultiBinary\ReadOperationFactory()),
                                (new OperationFactory\MultiBinary\UpdateOperationFactory()),
                                (new OperationFactory\MultiBinary\DeleteOperationFactory()),
                            ])));
                    }
                }
            }
        }
        return $endpoints;
    }

    private function createMatrixSubEndpoints($endpoint, $operation)
    {
        $endpoints = [];
        foreach ($operation->getSchemaFactories() as $schema) {
            if ($schema instanceof ContentClassSchemaFactory) {
                $class = \eZContentClass::fetchByIdentifier($schema->getClassIdentifier());
                /** @var \eZContentClassAttribute $classAttribute */
                foreach ($class->dataMap() as $classAttribute) {

                    if ($classAttribute->attribute('data_type_string') == \eZMatrixType::DATA_TYPE_STRING) {

                        $identifier = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                            $class,
                            $classAttribute
                        )->providePropertyIdentifier();

                        $matrixPath = $endpoint->getPath() . '/' . $identifier;
                        $this->log("Create matrix endpoint $matrixPath for attribute " . $class->attribute('identifier') . '/' . $classAttribute->attribute('identifier'));
                        $endpoints[$matrixPath] = (new EndpointFactory\MatrixEndpointFactory($classAttribute->attribute('id')))
                            ->setPath($matrixPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection((new OperationFactoryCollection([
                                (new OperationFactory\Matrix\CreateOperationFactory()),
                                (new OperationFactory\Matrix\ListOperationFactory()),
                            ])));

                        $matrixPath = $matrixPath . '/{matrixItemId}';
                        $endpoints[$matrixPath] = (new EndpointFactory\MatrixEndpointFactory($classAttribute->attribute('id')))
                            ->setPath($matrixPath)
                            ->setTags($endpoint->getTags())
                            ->setParentEndpointFactory($endpoint)
                            ->setParentOperationFactory($operation)
                            ->setOperationFactoryCollection((new OperationFactoryCollection([
                                (new OperationFactory\Matrix\ReadOperationFactory()),
                                (new OperationFactory\Matrix\UpdateOperationFactory()),
                                (new OperationFactory\Matrix\DeleteOperationFactory()),
                            ])));
                    }
                }
            }
        }

        return $endpoints;
    }

    private function createRelationsSubEndpoints($endpoint, $operation)
    {
        $endpoints = [];

        foreach ($operation->getSchemaFactories() as $schema) {
            if ($schema instanceof ContentClassSchemaFactory) {
                $class = \eZContentClass::fetchByIdentifier($schema->getClassIdentifier());
                /** @var \eZContentClassAttribute $classAttribute */
                foreach ($class->dataMap() as $classAttribute) {

                    if ($classAttribute->attribute('data_type_string') == \eZObjectRelationListType::DATA_TYPE_STRING) {
                        /** @var array $classContent */
                        $classContent = $classAttribute->content();

//                        if ($classContent['selection_type'] == 0) {

                            $relatedEndpoints = [];
                            if (isset($classContent['default_placement']['node_id']) && (int)$classContent['default_placement']['node_id'] > 0) {
                                $relatedEndpoints = $this->findEndpointsByNodeId($classContent['default_placement']['node_id']);

                                if (isset($classContent['class_constraint_list']) && !empty($classContent['class_constraint_list'])) {
                                    if (empty($relatedEndpoints)) {
                                        $relatedEndpoints = $this->createReadOnlyEndpoints(
                                            $classContent['default_placement']['node_id'],
                                            $classContent['class_constraint_list']
                                        );
                                        if (!empty($relatedEndpoints)) {
                                            foreach ($relatedEndpoints as $relatedReadOnlyEndpointPath => $relatedReadOnlyEndpoint) {
//                                                $this->log($class->attribute('identifier') . '/' . $classAttribute->attribute('identifier') . ' ' . $classContent['default_placement']['node_id'] . ' ' .
//                                                    implode(',', $classContent['class_constraint_list']) . ' ' . $relatedReadOnlyEndpoint->getPath(), 'error');
                                                $this->endpoints[$relatedReadOnlyEndpointPath] = $relatedReadOnlyEndpoint;
                                            }
                                        }
                                    } else {
                                        foreach ($relatedEndpoints as $relatedEndpoint) {
                                            $this->log("Append to node endpoint {$relatedEndpoint->getPath()} classes " . implode(', ', $classContent['class_constraint_list']));
                                            $relatedEndpoint->appendClassIdentifierList((array)$classContent['class_constraint_list']);
                                        }
                                    }
                                }
                            }elseif (isset($classContent['class_constraint_list']) && !empty($classContent['class_constraint_list'])) {

                                foreach ($classContent['class_constraint_list'] as $index => $classIndentifier){
                                    if (!\eZContentClass::classIDByIdentifier($classIndentifier)){
                                        Logger::instance()->error("Class not found", ['identifier' => $classIndentifier, 'method' => __METHOD__]);
                                        unset($classContent['class_constraint_list'][$index]);
                                    }
                                }

                                if (!empty($classContent['class_constraint_list'])) {

                                    $relatedEndpoints = $this->findEndpointsByClasses($classContent['class_constraint_list']);
                                    if (empty($relatedEndpoints)) {
                                        //non è configurato un nodo predefinito: aggiungo a miscellanea
                                        $relatedEndpoints = $this->appendToMiscEndpoints($classContent['class_constraint_list']);
                                    }
/*
                                    //non è configurato un nodo predefinito: cerco di capire se tutti gli oggetti previsti sono sotto a un unico nodo
                                    $contentSearch = new ContentSearch();
                                    $contentSearch->setEnvironment(new \DefaultEnvironmentSettings());
                                    $search = $contentSearch->search('classes [' . implode(',', $classContent['class_constraint_list']) . '] facets [raw[meta_main_parent_node_id_si]] limit 1');

                                    //print_r([$classContent['class_constraint_list'],$search->facets[0]['data']]);

                                    if (isset($search->facets[0]['data']) && count($search->facets[0]['data']) == 1){
                                        $nodeIdList = array_keys($search->facets[0]['data']);
                                        $nodeId = $nodeIdList[0];
                                        $relatedEndpoints = $this->findEndpointsByNodeId($nodeId);
                                        if (empty($relatedEndpoints)) {
                                            $relatedEndpoints = $this->createReadOnlyEndpoints(
                                                $nodeId,
                                                $classContent['class_constraint_list']
                                            );
                                            if (!empty($relatedEndpoints)) {
                                                foreach ($relatedEndpoints as $relatedReadOnlyEndpointPath => $relatedReadOnlyEndpoint) {
//                                                    $this->log($class->attribute('identifier') . '/' . $classAttribute->attribute('identifier') . ' ' . $nodeId . ' ' .
//                                                        implode(',', $classContent['class_constraint_list']) . ' ' . $relatedReadOnlyEndpoint->getPath(), 'error');
                                                    $this->endpoints[$relatedReadOnlyEndpointPath] = $relatedReadOnlyEndpoint;
                                                }
                                            }
                                        } else {
                                            foreach ($relatedEndpoints as $relatedEndpoint) {
                                                $this->log("Append to node endpoint {$relatedEndpoint->getPath()} classes " . implode(', ', $classContent['class_constraint_list']));
                                                $relatedEndpoint->appendClassIdentifierList((array)$classContent['class_constraint_list']);
                                            }
                                        }
                                    }
*/
                                }
                            }

                            $relatedEndpoint = null;
                            if (!empty($relatedEndpoints)) {
                                $relatedEndpoint = array_pop($relatedEndpoints);
                                //$this->log($relatedEndpoint->getPath(), 'warning');
                            }

                            $identifier = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                                $class,
                                $classAttribute
                            )->providePropertyIdentifier();

                            $relationsPath = $endpoint->getPath() . '/' . $identifier;
                            $this->log("Create relation endpoint $relationsPath for attribute " . $class->attribute('identifier') . '/' . $classAttribute->attribute('identifier'));
                            $endpoints[$relationsPath] = (new EndpointFactory\RelationsEndpointFactory($classAttribute->attribute('id')))
                                ->setPath($relationsPath)
                                ->setTags($endpoint->getTags())
                                ->setParentEndpointFactory($endpoint)
                                ->setParentOperationFactory($operation)
                                ->setOperationFactoryCollection((new OperationFactoryCollection([
                                    (new OperationFactory\Relations\CreateOperationFactory()),
                                    (new OperationFactory\Relations\ListOperationFactory()),
                                ])));

                            if ($relatedEndpoint instanceof EndpointFactory\NodeClassesEndpointFactory) {
                                $endpoints[$relationsPath]->setRelatedEndpoint($relatedEndpoint);
                            }

                            $relationsPath = $relationsPath . '/{relatedItemId}';
                            $endpoints[$relationsPath] = (new EndpointFactory\RelationsEndpointFactory($classAttribute->attribute('id')))
                                ->setPath($relationsPath)
                                ->setTags($endpoint->getTags())
                                ->setParentEndpointFactory($endpoint)
                                ->setParentOperationFactory($operation)
                                ->setOperationFactoryCollection((new OperationFactoryCollection([
                                    (new OperationFactory\Relations\ReadOperationFactory()),
                                    (new OperationFactory\Relations\UpdateOperationFactory()),
                                    (new OperationFactory\Relations\DeleteOperationFactory()),
                                ])));

                            if ($relatedEndpoint instanceof EndpointFactory\NodeClassesEndpointFactory) {
                                $endpoints[$relationsPath]->setRelatedEndpoint($relatedEndpoint);
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
    private function findEndpointsByNodeId($nodeId)
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
     * @param string[] $classes
     * @return EndpointFactory\NodeClassesEndpointFactory[]
     */
    private function findEndpointsByClasses($classes)
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

    /**
     * @param $nodeId
     * @param $classes
     * @return EndpointFactory\NodeClassesEndpointFactory[]
     */
    private function createReadOnlyEndpoints($nodeId, $classes)
    {
        $endpoints = [];

        $node = \eZContentObjectTreeNode::fetch($nodeId);
        if (!$node instanceof \eZContentObjectTreeNode) {
            return $endpoints;
        }

        $path = '/' . strtolower($node->urlAlias());
        $tag = str_replace('/', '-', strtolower($node->urlAlias()));

        $this->log("Create readonly endpoint $path for classes " . implode(', ', $classes));
        $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($nodeId, $classes))
            ->setPath($path)
            ->addTag($tag)
            ->setOperationFactoryCollection(new OperationFactoryCollection([
                (new OperationFactory\ContentObject\SearchOperationFactory()),
            ]));

        $path .= '/{id}';
        $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($nodeId, $classes))
            ->setPath($path)
            ->addTag($tag)
            ->setOperationFactoryCollection(new OperationFactoryCollection([
                (new OperationFactory\ContentObject\ReadOperationFactory()),
            ]));

        return $endpoints;
    }

    private function appendToMiscEndpoints($classes)
    {
        if ($this->miscellanea === null) {
            $endpoints = [];

            $node = \eZContentObjectTreeNode::fetch(\eZINI::instance('content.ini')->variable('NodeSettings', 'RootNode'));

            $path = '/miscellanea';
            $tag = 'miscellanea';

            $this->log("Create miscellanea readonly endpoint $path for classes " . implode(', ', $classes));
            $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($node->attribute('node_id'), $classes))
                ->setPath($path)
                ->addTag($tag)
                ->setOperationFactoryCollection(new OperationFactoryCollection([
                    (new OperationFactory\ContentObject\SearchOperationFactory()),
                ]));

            $path .= '/{id}';
            $endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory($node->attribute('node_id'), $classes))
                ->setPath($path)
                ->addTag($tag)
                ->setOperationFactoryCollection(new OperationFactoryCollection([
                    (new OperationFactory\ContentObject\ReadOperationFactory()),
                ]));

            $this->miscellanea = $endpoints;
            foreach ($this->miscellanea as $path => $endpoint) {
                $this->endpoints[$path] = $endpoint;
            }
        }else{
            foreach ($this->miscellanea as $endpoint) {
                $this->log("Append to node endpoint {$endpoint->getPath()} classes " . implode(', ', $classes));
                $endpoint->appendClassIdentifierList((array)$classes);
            }
        }

        return $this->miscellanea;
    }
}