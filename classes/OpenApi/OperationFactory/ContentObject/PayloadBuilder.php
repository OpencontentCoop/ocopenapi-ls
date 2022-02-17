<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

class PayloadBuilder extends \Opencontent\Opendata\Rest\Client\PayloadBuilder
{
    const CREATE = 1;

    const UPDATE = 2;

    const TRANSLATE = 3;

    const PATCH = 4;

    private $actions = [];

    public function isAction($action){
        return isset($this->actions[$action]);
    }

    public function appendAction($action)
    {
        $this->actions[$action] = $action;
    }

    public function removeAction($action)
    {
        unset($this->actions[$action]);
    }

    public function setAction($action)
    {
        $this->actions = [];
        $this->actions[$action] = $action;
    }
}
