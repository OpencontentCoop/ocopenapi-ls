<?php

namespace Opencontent\OpenApi\OperationFactory\Tags;

use ezpRestMvcResult;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\OperationFactory\CacheAwareInterface;
use Opencontent\OpenApi\OperationFactory\SearchOperationFactory;
use erasys\OpenApi\Spec\v3 as OA;

class ListOperationFactory extends SearchOperationFactory implements CacheAwareInterface
{
    use TagFetchTrait;

    const MAX_LIMIT = 50;

    const DEFAULT_LIMIT = 10;

    protected $referenceTagList;

    public function setResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): void
    {
        header("Cache-Control: public, must-revalidate, max-age=600, s-maxage=600"); //@todo make configurable
        header("X-Cache-Tags: tags");
        header("Vary: Accept-Language");
    }

    public function hasResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): bool
    {
        return true;
    }

    protected function getCurrentRequestParameters(): array
    {
        $parameters = [
            'offset' => 0,
            'limit' => 0,
        ];
        foreach ($this->generateSearchParameters() as $parameter) {
            if ($this->getCurrentRequestParameter($parameter->name)) {
                $parameters[$parameter->name] = $this->getCurrentRequestParameter($parameter->name);
            }
        }

        return $parameters;
    }

    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $path = $endpointFactory->getBaseUri() . $endpointFactory->getPath();
        $parameters = $this->getCurrentRequestParameters();
        $result = [
            'items' => $this->fetchTagList($parameters),
            'self' => $path . '?' . http_build_query($parameters),
            'prev' => null,
            'next' => null,
            'count' => $this->fetchTagCount($parameters),
        ];

        if ($result['count'] > ($parameters['offset'] + $parameters['limit'])) {
            $nextParameters = $parameters;
            $nextParameters['offset'] += $nextParameters['limit'];
            $result['next'] = $path . '?' . http_build_query($nextParameters);
        }

        if ($parameters['offset'] > 0) {
            $prevParameters = $parameters;
            $prevParameters['offset'] -= $prevParameters['limit'];
            if ($prevParameters['offset'] < 0) {
                $prevParameters['offset'] = 0;
            }
            $result['prev'] = $path . '?' . http_build_query($prevParameters);
        }

        $response = new \ezpRestMvcResult();
        $response->variables = $result;
        return $response;
    }

    protected function generateSearchParameters()
    {
        return [
            new OA\Parameter('term', OA\Parameter::IN_QUERY, 'Search by term', [
                'schema' => $this->generateSchemaProperty(['type' => 'string', 'nullable' => true]),
            ]),
            new OA\Parameter('limit', OA\Parameter::IN_QUERY, 'Limit to restrict the number of entries on a page', [
                'schema' => $this->generateSchemaProperty(
                    [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => static::MAX_LIMIT,
                        'default' => static::DEFAULT_LIMIT,
                        'nullable' => true,
                    ]
                ),
            ]),
            new OA\Parameter(
                'offset',
                OA\Parameter::IN_QUERY,
                'Numeric offset of the first element provided on a page representing a collection request',
                [
                    'schema' => $this->generateSchemaProperty(['type' => 'integer']),
                ]
            ),
            $this->generateHeaderLanguageParameter(),
        ];
    }
}