<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

class PayloadBuilder extends \Opencontent\Opendata\Rest\Client\PayloadBuilder
{
    const CREATE = 1;

    const UPDATE = 2;

    const TRANSLATE = 3;

    const PATCH = 4;

    public $action;
}