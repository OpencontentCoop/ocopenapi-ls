<?php

namespace Opencontent\OpenApi\EndpointDiscover;

use Opencontent\OpenApi\EndpointFactory\TagsEndpointFactory;
use Opencontent\OpenApi\EndpointFactoryCollection;
use Opencontent\OpenApi\EndpointFactoryProvider;
use Opencontent\OpenApi\OperationFactory\Tags\ChildrenOperationFactory;
use Opencontent\OpenApi\OperationFactory\Tags\ListOperationFactory;
use Opencontent\OpenApi\OperationFactory\Tags\ParentOperationFactory;
use Opencontent\OpenApi\OperationFactory\Tags\ReadOperationFactory;
use Opencontent\OpenApi\OperationFactoryCollection;

class TagsEndpointFactoryProvider extends EndpointFactoryProvider
{
    public function getEndpointFactoryCollection()
    {
        $list = (new TagsEndpointFactory())
            ->setPath('/tassonomie')
            ->setTags(['tassonomie'])
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    new ListOperationFactory(),
                ])
            );
        $read = (new TagsEndpointFactory())
            ->setPath('/tassonomie/{id}')
            ->setTags(['tassonomie'])
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    $readOperation = new ReadOperationFactory(),
                ])
            );
        $childrenOperation = (new ChildrenOperationFactory());
        $childrenOperation->setParentOperationFactory($readOperation);
        $childrenOperation->setParentEndpointFactory($read);
        $children = (new TagsEndpointFactory())
            ->setPath('/tassonomie/{id}/children')
            ->setTags(['tassonomie'])
            ->setOperationFactoryCollection(
                new OperationFactoryCollection([
                    $childrenOperation,
                ])
            );
        return new EndpointFactoryCollection([
            $list,
            $read,
            $children,
        ]);
    }

}