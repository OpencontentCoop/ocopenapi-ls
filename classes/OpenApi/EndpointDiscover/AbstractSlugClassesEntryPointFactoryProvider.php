<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\OperationFactory\Slug;
use Opencontent\OpenApi\OperationFactoryCollection;

abstract class AbstractSlugClassesEntryPointFactoryProvider extends EndpointFactoryProvider
{
    /**
     * @var EndpointFactory[]|EndpointFactory\NodeClassesEndpointFactory[]
     */
    private $endpoints;

    public function getEndpointFactoryCollection()
    {
        $this->build();
        return new EndpointFactoryCollection($this->endpoints);
    }

    protected function build()
    {
        $this->endpoints = [];
        $prefix = $this->getPrefix();
        $classes = $this->getClassIdentifiers();
        $nodeIdMap = $this->getSlugIdMap();
        $tag = $this->getTag();

        if (count($nodeIdMap)) {

            $slugEnum = array_keys($nodeIdMap);
            $slugLabel = $this->getSlugLabel();
            sort($slugEnum);

            $path = $prefix . '/{' . $slugLabel . '}';
            $this->endpoints[$path] = (new EndpointFactory\SlugClassesEntryPointFactory(
                $slugLabel,
                $classes,
                $nodeIdMap
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
            $this->endpoints[$path] = (new EndpointFactory\SlugClassesEntryPointFactory(
                $slugLabel,
                $classes,
                $nodeIdMap
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
        }

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

    abstract protected function getSlugLabel();

    abstract protected function getPrefix();

    abstract protected function getClassIdentifiers();

    abstract protected function getSlugIdMap();

    abstract protected function getTag();

}