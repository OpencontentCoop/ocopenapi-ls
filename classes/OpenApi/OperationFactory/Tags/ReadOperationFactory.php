<?php

namespace Opencontent\OpenApi\OperationFactory\Tags;

use ezpRestMvcResult;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\OperationFactory\CacheAwareInterface;
use Opencontent\OpenApi\OperationFactory\ReadOperationFactory as BaseReadOperationFactory;

class ReadOperationFactory extends BaseReadOperationFactory implements CacheAwareInterface
{
    use TagFetchTrait;

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

    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)) {
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }

        $result = new \ezpRestMvcResult();
        $result->variables = $this->fetchTagByRemoteId($requestId);

        return $result;
    }


}