<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\OperationFactory;

class NodeClassesEndpointFactoryProvider extends EndpointFactoryProvider
{
    use LoggableTrait;
    use SubEndpointProviderTrait;

    /**
     * @var \eZContentObjectTreeNode
     */
    private $node;

    /**
     * @var array
     */
    private $classIdentifierList;

    /**
     * @var EndpointFactory[]|EndpointFactory\NodeClassesEndpointFactory[]
     */
    private $endpoints;

    /**
     * @var string|null
     */
    private $path;

    /**
     * @var array
     */
    private $tags;

    public function __construct(
        \eZContentObjectTreeNode $node,
        array $classIdentifierList,
        ?string $path = null,
        array $tags = []
    ) {
        $this->node = $node;
        $this->classIdentifierList = $classIdentifierList;
        $this->path = $path ?? '/' . strtolower($node->urlAlias());
        $this->tags = $tags;
    }

    public function getEndpointFactoryCollection()
    {
        if ($this->endpoints === null) {
            $this->endpoints[$this->path] = (new EndpointFactory\NodeClassesEndpointFactory(
                $this->node->attribute('node_id'), $this->classIdentifierList
            ))->setPath($this->path)
                ->setTags($this->tags)
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        new OperationFactory\ContentObject\FilteredSearchOperationFactory(),
                        new OperationFactory\ContentObject\CreateOperationFactory(),
                    ])
                );

            $path = $this->path . '/{id}';
            $this->endpoints[$path] = (new EndpointFactory\NodeClassesEndpointFactory(
                $this->node->attribute('node_id'), $this->classIdentifierList
            ))->setPath($path)
                ->setTags($this->tags)
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        new OperationFactory\ContentObject\ReadOperationFactory(),
                        new OperationFactory\ContentObject\UpdateOperationFactory(),
                        new OperationFactory\ContentObject\MergePatchOperationFactory(),
                        new OperationFactory\ContentObject\DeleteOperationFactory(),
                    ])
                );

            foreach ($this->endpoints as $endpoint) {
                if ($endpoint instanceof EndpointFactory\NodeClassesEndpointFactory
                    && $endpoint->hasOperationMethod('get')
                    && $endpoint->getOperationByMethod('get')
                    instanceof OperationFactory\ContentObject\ReadOperationFactory) {
                    $operation = $endpoint->getOperationByMethod('get');

                    $this->createSubEndpoints($endpoint, $operation);
                }
            }
        }

        return new EndpointFactoryCollection(array_values($this->endpoints));
    }
}