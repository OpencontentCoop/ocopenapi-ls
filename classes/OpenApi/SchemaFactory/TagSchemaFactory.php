<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaFactory;

class TagSchemaFactory extends SchemaFactory
{
    protected $name = 'TaxonomyTag';

    public function getItemIdLabel()
    {
        return 'id';
    }

    public function generateSchema()
    {
        $schema = new OA\Schema();
        $schema->title = $this->name;
        $schema->type = 'object';
        $schema->properties = [
            'id' => $this->generateSchemaProperty([
                'type' => 'string',
                'description' => 'Tag id',
            ]),
            'term' => $this->generateSchemaProperty([
                'type' => 'string',
                'description' => 'Tag name',
            ]),
            'isSynonymOf' => $this->generateSchemaProperty([
                'type' => 'string',
                'description' => 'Main tag id',
                'nullable' => true,
            ]),
            'voc' => $this->generateSchemaProperty([
                'type' => 'string',
                'format' => 'uri',
                'description' => 'Controlled vocabulary link',
                'nullable' => true,
            ]),
            'description' => $this->generateSchemaProperty([
                'type' => 'string',
                'description' => 'Tag description',
                'nullable' => true,
            ]),
            'translations' => $this->generateSchemaProperty([
                'type' => 'array',
                'items' => $this->generateSchemaProperty([
                    'type' => 'object',
                    'properties' => [
                        'term' => $this->generateSchemaProperty([
                            'type' => 'string',
                            'description' => 'Translated term',
                        ]),
                        'language' => $this->generateSchemaProperty([
                            'type' => 'string',
                            'description' => 'Translation language',
                        ]),
                    ],
                ]),
            ]),
            'synonyms' => $this->generateSchemaProperty([
                'type' => 'array',
                'items' => $this->generateSchemaProperty([
                    'type' => 'object',
                    'properties' => [
                        'term' => $this->generateSchemaProperty([
                            'type' => 'string',
                            'description' => 'Synonym term',
                        ]),
                        'language' => $this->generateSchemaProperty([
                            'type' => 'string',
                            'description' => 'Synonym language',
                        ]),
                    ],
                ]),
            ]),
            'parent' => [
                'allOf' => [new OA\Reference('#/components/schemas/' . $this->name)],
                'nullable' => true,
            ],
        ];

        return $schema;
    }

    /**
     * @inheritDoc
     */
    public function generateRequestBody()
    {
        return new OA\RequestBody([
            'application/json' => new OA\MediaType([
                'schema' => new OA\Reference('#/components/schemas/' . $this->name),
            ]),
        ]);
    }

    public function serialize()
    {
        return serialize([
            'name' => $this->name,
        ]);
    }
}