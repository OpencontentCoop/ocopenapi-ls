<?php

namespace Opencontent\OpenApi\OperationFactory;

use Opencontent\OpenApi\OperationFactory;

abstract class PatchOperationFactory extends OperationFactory
{
    protected $method = 'patch';
}