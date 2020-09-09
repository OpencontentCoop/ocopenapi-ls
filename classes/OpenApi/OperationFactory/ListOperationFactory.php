<?php

namespace Opencontent\OpenApi\OperationFactory;

use erasys\OpenApi\Spec\v3 as OA;

class ListOperationFactory extends GetOperationFactory
{
    protected $name = 'list';

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        return [
            '200' => new OA\Response('Successful response.', [
                'application/json' => new OA\MediaType([
                    'schema' => $this->generateListSchema()
                ])
            ], $this->generateResponseHeaders()),
            '400' => new OA\Response('Invalid input provided', null, $this->generateResponseHeaders(true)),
            '403' => new OA\Response('Forbidden', null, $this->generateResponseHeaders(true)),
            '404' => new OA\Response('Not found', null, $this->generateResponseHeaders(true)),
            '500' => new OA\Response('Internal error', null, $this->generateResponseHeaders(true)),
        ];
    }

    protected function generateListSchema()
    {
        $searchResultSchema = new OA\Schema();
        $searchResultSchema->type = 'object';
        $searchResultSchema->properties = [
            'items' => $this->generateSchemaProperty(['type' => 'array', 'items' => $this->generateSchemasReference()]),
        ];

        return $searchResultSchema;
    }
}