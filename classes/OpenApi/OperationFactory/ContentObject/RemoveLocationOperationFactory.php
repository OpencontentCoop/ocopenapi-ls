<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use Opencontent\OpenApi\OperationFactory;

class RemoveLocationOperationFactory extends OperationFactory
{
    protected $name = 'remove_location';

    protected $method = 'delete';
}