<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\OperationFactory\ContentObject\ReadOperationFactory;
use Opencontent\OpenApi\OperationFactory\Slug;
use Opencontent\OpenApi\OperationFactoryCollection;
use Opencontent\OpenApi\SchemaFactory\SlugClassesClassSchemaSerializer;

abstract class AbstractSlugClassesEntryPointFactoryProvider extends EndpointFactoryProvider
{
    /**
     * @var EndpointFactory[]|EndpointFactory\NodeClassesEndpointFactory[]
     */
    protected $endpoints;

    public function getEndpointFactoryCollection()
    {
        $this->endpoints = [];
        $this->build();
        return new EndpointFactoryCollection($this->endpoints);
    }

    protected function build()
    {
        $prefix = $this->getPrefix();
        $classes = $this->getClassIdentifiers();
        $nodeIdMap = (array)$this->getSlugIdMap();
        $tag = $this->getTag();
        $endpoints = [];

        if (count($nodeIdMap)) {
            $slugEnum = array_keys($nodeIdMap);
            $slugLabel = $this->getSlugLabel();
            sort($slugEnum);

            $path = $prefix . '/{' . $slugLabel . '}';
            $endpoints[$path] = (new EndpointFactory\SlugClassesEntryPointFactory(
                $slugLabel,
                $classes,
                $nodeIdMap,
                $this->getSerializer()
            ))
                ->setPath($path)
                ->addTag($tag)
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        (new Slug\SearchOperationFactory($slugLabel, $slugEnum)),
                        (new Slug\CreateOperationFactory($slugLabel, $slugEnum)),
                    ])
                );

            $path = $prefix . '/{' . $slugLabel . '}/{id}';
            $endpoints[$path] = (new EndpointFactory\SlugClassesEntryPointFactory(
                $slugLabel,
                $classes,
                $nodeIdMap,
                $this->getSerializer()
            ))
                ->setPath($path)
                ->addTag($tag)
                ->setOperationFactoryCollection(
                    new OperationFactoryCollection([
                        (new Slug\ReadOperationFactory($slugLabel, $slugEnum)),
                        (new Slug\MergePatchOperationFactory($slugLabel, $slugEnum)),
                        (new Slug\DeleteOperationFactory($slugLabel, $slugEnum)),
                    ])
                );

            $operation = $endpoints[$path]->getOperationByMethod('get');
            if ($operation instanceof ReadOperationFactory) {
                $subEndpoints = Utils::createMatrixSubEndpoints($endpoints[$path], $operation);
                foreach ($subEndpoints as $subPath => $subEndpoint){
                    $endpoints[$subPath] = $subEndpoint;
                }
            }
        }

        $sort = [];
        foreach ($endpoints as $endpoint) {
            $path = $endpoint->getPath();
            $path = str_replace('{', 'aaa', $path);
            $sortKey = \eZCharTransform::instance()->transformByGroup($path, 'identifier');
            $sort[$sortKey] = $endpoint;
        }
        ksort($sort);
        $endpoints = array_values($sort);
        $this->endpoints = array_merge($this->endpoints, array_values($endpoints));
    }

    abstract protected function getSlugLabel();

    abstract protected function getPrefix();

    abstract protected function getClassIdentifiers();

    abstract protected function getSlugIdMap();

    abstract protected function getTag();

    protected function getSerializer()
    {
        return new SlugClassesClassSchemaSerializer();
    }

}