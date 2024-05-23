<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use eZCharTransform;
use eZCLI;
use eZContentClass;
use eZContentObjectTreeNode;
use eZPolicy;
use eZPolicyLimitation;
use eZPolicyLimitationValue;
use eZRole;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\OperationFactoryCollection;

class RoleEndpointFactoryDiscover extends EndpointFactoryProvider
{
    use LoggableTrait;
    use SubEndpointProviderTrait;

    /**
     * @var EndpointFactory[]|EndpointFactory\NodeClassesEndpointFactory[]
     */
    private $endpoints;

    private $excludeRoles = [];

    public function __construct(eZCLI $cli = null)
    {
        $this->cli = $cli;
        if (\eZINI::instance('ocopenapi.ini')
            ->hasVariable('RoleDiscover', 'Exclude')) {
            $this->excludeRoles = (array) \eZINI::instance('ocopenapi.ini')
                ->variable('RoleDiscover', 'Exclude');
        }
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
                $sortKey = eZCharTransform::instance()->transformByGroup($path, 'identifier');
                $sort[$sortKey] = $endpoint;
            }
            ksort($sort);
            $this->endpoints = array_values($sort);
        }

        return new EndpointFactoryCollection($this->endpoints);
    }

    private function getOperationFactory(string $type): OperationFactory
    {
        switch ($type) {
            case 'search':
                return new OperationFactory\ContentObject\FilteredSearchOperationFactory();
            case 'create':
                return new OperationFactory\ContentObject\CreateOperationFactory();
            case 'read':
                return new OperationFactory\ContentObject\ReadOperationFactory();
            case 'update':
                return new OperationFactory\ContentObject\UpdateOperationFactory();
            case 'merge':
                return new OperationFactory\ContentObject\MergePatchOperationFactory();
            case 'delete':
                return new OperationFactory\ContentObject\DeleteOperationFactory();
        }

        throw new \InvalidArgumentException(sprintf('OperationFactory type %s not mapped', $type));
    }

    private function discoverFromRoles()
    {
        /** @var eZRole[] $roles */
        $roles = eZRole::fetchList();
        foreach ($roles as $role) {
            if (in_array($role->attribute('name'), $this->excludeRoles)) {
                $this->log('EXCLUDE ##### ' . $role->attribute('name') . ' #####', 'error');
                continue;
            }
            $this->log('##### ' . $role->attribute('name') . ' #####', 'error');
            /** @var eZPolicy $policy */
            foreach ($role->policyList() as $policy) {
                if ($policy->attribute('module_name') == 'content' && $policy->attribute('function_name') == 'create') {
                    /** @var eZContentObjectTreeNode[] $subtree */
                    $subtree = [];
                    $classes = [];
                    $parentClasses = [];

                    /** @var eZPolicyLimitation $limitation */
                    foreach ($policy->limitationList() as $limitation) {
                        if ($limitation->attribute('identifier') == 'Node') {
                            /** @var eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $node = eZContentObjectTreeNode::fetch($value->attribute('value'));
                                if ($node instanceof eZContentObjectTreeNode) {
                                    $this->log('  (node) ' . $node->urlAlias(), 'warning');
                                    $subtree[$node->attribute('node_id')] = $node;
                                } else {
                                    $this->log('  (node) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                        if ($limitation->attribute('identifier') == 'Subtree') {
                            /** @var eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $node = eZContentObjectTreeNode::fetchByPath($value->attribute('value'));
                                if ($node instanceof eZContentObjectTreeNode) {
                                    $this->log('  (subtree) ' . $node->urlAlias(), 'warning');
                                    $subtree[$node->attribute('node_id')] = $node;
                                } else {
                                    $this->log('  (subtree) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                        if ($limitation->attribute('identifier') == 'Class') {
                            /** @var eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $classIdentifier = eZContentClass::classIdentifierByID($value->attribute('value'));
                                if ($classIdentifier) {
                                    $classes[] = $classIdentifier;
                                    $this->log('  (class) ' . $classIdentifier, 'warning');
                                } else {
                                    $this->log('  (class) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                        if ($limitation->attribute('identifier') == 'ParentClass') {
                            /** @var eZPolicyLimitationValue $value */
                            foreach ($limitation->valueList() as $value) {
                                $classIdentifier = eZContentClass::classIdentifierByID($value->attribute('value'));
                                if ($classIdentifier) {
                                    $parentClasses[] = $classIdentifier;
                                    $this->log('  (parent) ' . $classIdentifier, 'warning');
                                } else {
                                    $this->log('  (parent) ' . $value->attribute('value'), 'error');
                                }
                            }
                        }
                    }

                    $classes = array_unique($classes);

                    if (empty($classes)) {
                        continue;
                    }

                    foreach ($subtree as $item) {
                        $pathGroup = [
                            [
                                'nodes' => [$item],
                                'path_suffix' => '',
                                'operations' => new OperationFactoryCollection([
                                    $this->getOperationFactory('create'),
                                    $this->getOperationFactory('search'),
                                ]),
                            ],
                            [
                                'nodes' => [$item],
                                'path_suffix' => '/{id}',
                                'operations' => new OperationFactoryCollection([
                                    $this->getOperationFactory('read'),
                                    $this->getOperationFactory('update'),
                                    $this->getOperationFactory('merge'),
                                    $this->getOperationFactory('delete'),
                                ]),
                            ],
                        ];

                        if (count($parentClasses) > 0) {
                            continue;
                        }

                        foreach ($pathGroup as $pathGroupItem) {
                            /** @var eZContentObjectTreeNode $node */
                            foreach ($pathGroupItem['nodes'] as $node) {
                                $path = '/' . strtolower($node->urlAlias()) . $pathGroupItem['path_suffix'];
                                $tag = str_replace('/', '-', strtolower($node->urlAlias()));
                                if (isset($this->endpoints[$path]) && $this->endpoints[$path] instanceof EndpointFactory\NodeClassesEndpointFactory) {
                                    $this->log("  Append to node endpoint $path classes " . implode(', ', $classes));
                                    $this->endpoints[$path]->appendClassIdentifierList($classes);
                                } else {
                                    $this->log("  Create node endpoint $path for classes " . implode(', ', $classes));
                                    $this->endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory(
                                        $item->attribute('node_id'), $classes
                                    ))
                                        ->setPath($path)
                                        ->addTag($tag)
                                        ->setOperationFactoryCollection($pathGroupItem['operations'])
                                        ->setRoleName($role->attribute('name'));
                                }
                            }
                        }
                    }
                    $this->log(' ');
                }
            }
        }

        $this->log(' ');
        $this->log('Build sub endpoints', 'error');

        foreach ($this->endpoints as $endpoint) {
            if ($endpoint instanceof EndpointFactory\NodeClassesEndpointFactory
                && $endpoint->hasOperationMethod('get')
                && $endpoint->getOperationByMethod('get')
                    instanceof OperationFactory\ContentObject\ReadOperationFactory) {
                $operation = $endpoint->getOperationByMethod('get');

                $this->log('  ...analyze ' . $endpoint->getPath(), 'warning');
                $this->createSubEndpoints($endpoint, $operation);
            }
        }
    }
}