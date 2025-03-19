<?php

namespace Opencontent\OpenApi\OperationFactory\Tags;

use erasys\OpenApi\Spec\v3 as OA;
use ezpRestMvcResult;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryTrait;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\OperationFactory\CacheAwareInterface;

class ChildrenOperationFactory extends ListOperationFactory implements ChildEndpointFactoryInterface,
                                                                       CacheAwareInterface
{
    use ChildEndpointFactoryTrait;

    public function setResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): void
    {
        header("Cache-Control: public, must-revalidate, s-maxage=600"); //@todo make configurable
        header("X-Cache-Tags: tags");
        header("Vary: Accept-Language");
    }

    public function hasResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): bool
    {
        return true;
    }

    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $parameters = $this->getCurrentRequestParameters();
        $parameters['main_only'] = true;
        $items = $this->fetchTagList($parameters);
        $count = $this->fetchTagCount($parameters);
        $path = $endpointFactory->getBaseUri() . $endpointFactory->getPath();
        $path = str_replace('{id}', $parameters['parent_remote_id'], $path);
        unset($parameters['parent_remote_id']);
        
        $result = [
            'items' => $items,
            'self' => $path . '?' . http_build_query($parameters),
            'prev' => null,
            'next' => null,
            'count' => $count,
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

    /**
     * @throws InvalidParameterException
     * @throws NotFoundException
     */
    protected function getCurrentRequestParameters(): array
    {
        $parameters = parent::getCurrentRequestParameters();
        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)) {
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }
        $parameters['parent_remote_id'] = $requestId;

        return $parameters;
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        $properties['parameters'] = [
                new OA\Parameter($this->getItemIdLabel(), OA\Parameter::IN_PATH, $this->getItemIdDescription(), [
                    'schema' => $this->generateSchemaProperty(['type' => 'string']),
                    'required' => true,
                ]),
            ] + $properties['parameters'];
        return $properties;
    }
}