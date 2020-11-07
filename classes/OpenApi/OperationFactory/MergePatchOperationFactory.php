<?php

namespace Opencontent\OpenApi\OperationFactory;

use erasys\OpenApi\Spec\v3 as OA;

class MergePatchOperationFactory extends PatchOperationFactory
{
    protected $name = 'merge';

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
            '400' => new OA\Response('Invalid input provided.', null, $this->generateResponseHeaders(true)),
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
            $this->generateHeaderLanguageParameter(),
        ];

        $schemaFactories = $this->getSchemaFactories();
        $allOf = [];
        foreach ($schemaFactories as $schemaFactory){
            $allOf[] = new OA\Reference('#/components/schemas/' . $schemaFactory->getName());
        }

        $properties['requestBody'] = [
            'content' => ['application/merge-patch+json' => new OA\MediaType([
                'schema' => new OA\Schema([
                    'allOf' => $allOf
                ])
            ])]
        ];

        return $properties;
    }
}