<?php

namespace Opencontent\OpenApi\OperationFactory;

use Opencontent\OpenApi\OperationFactory;
use erasys\OpenApi\Spec\v3 as OA;

class DeleteOperationFactory extends OperationFactory
{
    protected $name = 'delete';

    protected $method = 'delete';

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        return [
            '200' => new OA\Response('Successful response', null, $this->generateResponseHeaders()),
            '400' => new OA\Response('Invalid input provided', null, $this->generateResponseHeaders(true)),
            '403' => new OA\Response('Forbidden', null, $this->generateResponseHeaders(true)),
            '404' => new OA\Response('Not found', null, $this->generateResponseHeaders(true)),
            '500' => new OA\Response('Internal error', null, $this->generateResponseHeaders(true)),
        ];
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        $properties['parameters'] = [
            new OA\Parameter($this->getItemIdLabel(), OA\Parameter::IN_PATH, $this->getItemIdDescription(), [
                'schema' => $this->generateSchemaProperty(['type' => 'string']),
                'required' => true,
            ]),
        ];

        return $properties;
    }
}