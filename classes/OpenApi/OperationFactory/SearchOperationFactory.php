<?php

namespace Opencontent\OpenApi\OperationFactory;

use erasys\OpenApi\Spec\v3 as OA;

class SearchOperationFactory extends GetOperationFactory
{
    const MAX_LIMIT = 50;

    const DEFAULT_LIMIT = 50;

    protected $name = 'search';

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        return [
            '200' => new OA\Response('Successful response.', [
                'application/json' => new OA\MediaType([
                    'schema' => $this->generateSearchResultSchema()
                ]),
            ], $this->generateResponseHeaders()),
            '400' => new OA\Response('Invalid input provided.', null, $this->generateResponseHeaders(true)),
            '403' => new OA\Response('Forbidden', null, $this->generateResponseHeaders(true)),
            '404' => new OA\Response('Not found', null, $this->generateResponseHeaders(true)),
            '500' => new OA\Response('Internal error', null, $this->generateResponseHeaders(true)),
        ];
    }

    protected function generateSearchResultSchema()
    {
        $searchResultSchema = new OA\Schema();
        $searchResultSchema->type = 'object';
        $searchResultSchema->properties = [
            'items' => $this->generateSchemaProperty(['type' => 'array', 'items' => $this->generateSchemasReference()]),
            'self' => $this->generateSchemaProperty(['type' => 'string', 'title' => 'Current result page']),
            'prev' => $this->generateSchemaProperty(['type' => 'string', 'title' => 'Previous result page', 'nullable' => true]),
            'next' => $this->generateSchemaProperty(['type' => 'string', 'title' => 'Next result page', 'nullable' => true]),
            'count' => $this->generateSchemaProperty(['type' => 'integer', 'format' => 'int32', 'description' => 'Total number of items available.']),
        ];

        return $searchResultSchema;
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        $properties['parameters'] = $this->generateSearchParameters();
        return $properties;
    }

    protected function generateSearchParameters()
    {
        return [
            new OA\Parameter('searchTerm', OA\Parameter::IN_QUERY, 'Query parameter', [
                'schema' => $this->generateSchemaProperty(['type' => 'string', 'nullable' => true]),
            ]),
            new OA\Parameter('limit', OA\Parameter::IN_QUERY, 'Limit to restrict the number of entries on a page', [
                'schema' => $this->generateSchemaProperty(['type' => 'integer', 'format' => 'int32', 'minimum' => 1, 'maximum' => static::MAX_LIMIT, 'default' => static::DEFAULT_LIMIT, 'nullable' => true]),
            ]),
            new OA\Parameter('offset', OA\Parameter::IN_QUERY, 'Numeric offset of the first element provided on a page representing a collection request', [
                'schema' => $this->generateSchemaProperty(['type' => 'integer', 'format' => 'int32',]),
            ]),
            $this->generateHeaderLanguageParameter(),
        ];
    }
}