<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

class PayloadBuilder extends \Opencontent\Opendata\Rest\Client\PayloadBuilder
{
    const CREATE = 1;

    const UPDATE = 2;

    const TRANSLATE = 3;

    public $action;
}