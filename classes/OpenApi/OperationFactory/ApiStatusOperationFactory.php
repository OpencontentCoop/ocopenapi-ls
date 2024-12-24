<?php

namespace Opencontent\OpenApi\OperationFactory;

use Opencontent\OpenApi\EndpointFactory;
use erasys\OpenApi\Spec\v3 as OA;

class ApiStatusOperationFactory extends GetOperationFactory
{
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();

        $result->variables = ['status' => 'ok'];

        return $result;
    }

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        return [
            '200' => new OA\Response('Successful response', [
                'application/json' => new OA\MediaType([
                    'schema' => $this->generateSchemasReference()
                ])
            ], $this->generateResponseHeaders()),
            '500' => new OA\Response('Internal error', null, $this->generateResponseHeaders(true)),
        ];
    }
}