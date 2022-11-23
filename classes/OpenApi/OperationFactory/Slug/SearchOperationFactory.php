<?php

namespace Opencontent\OpenApi\OperationFactory\Slug;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory\SlugClassesEntryPointFactory;
use Opencontent\OpenApi\OperationFactory\ContentObject\SearchOperationFactory as SearchOperationFactoryBase;
use Opencontent\OpenApi\SchemaBuilder\ReferenceSchema;
use Opencontent\Opendata\Api\Values\SearchResults;

class SearchOperationFactory extends SearchOperationFactoryBase
{
    /**
     * @var SlugClassesEntryPointFactory
     */
    private $currentEndpointFactory;

    private $pageLabel;

    private $enum = [];

    public function __construct($pageLabel, $enum)
    {
        $this->pageLabel = $pageLabel;
        $this->enum = $enum;
        parent::__construct();
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        $schema = new ReferenceSchema();
        $schema->type = 'string';
        $schema->enum = $this->enum;
        array_unshift(
            $properties['parameters'],
            new OA\Parameter($this->pageLabel, OA\Parameter::IN_PATH, 'Page identifier', [
                'schema' => $schema,
                'required' => true,
            ])
        );

        return $properties;
    }

    protected function buildQueryParts($endpointFactory)
    {
        $this->currentEndpointFactory = $endpointFactory;
        $query = parent::buildQueryParts($endpointFactory);
        array_unshift($query, "raw[meta_main_parent_node_id_si] = " . (int)$this->currentEndpointFactory->getNodeId());

        return $query;
    }

    protected function buildResult(SearchResults $searchResults, $path)
    {
        $result = parent::buildResult($searchResults, $path);
        $result['self'] = $this->currentEndpointFactory->replacePrefix($result['self']);
        $result['prev'] = $this->currentEndpointFactory->replacePrefix($result['prev']);
        $result['next'] = $this->currentEndpointFactory->replacePrefix($result['next']);


        return $result;
    }


}