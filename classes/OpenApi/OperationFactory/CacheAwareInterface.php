<?php

namespace Opencontent\OpenApi\OperationFactory;

use ezpRestMvcResult;
use Opencontent\OpenApi\EndpointFactory;

interface CacheAwareInterface
{
    public function setResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): void;

    public function hasResponseHeaders(EndpointFactory $endpointFactory, ezpRestMvcResult $result): bool;
}