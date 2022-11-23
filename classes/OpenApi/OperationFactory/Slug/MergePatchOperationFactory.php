<?php

namespace Opencontent\OpenApi\OperationFactory\Slug;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\OperationFactory\ContentObject\MergePatchOperationFactory as MergePatchOperationFactoryBase;
use Opencontent\OpenApi\SchemaBuilder\ReferenceSchema;

class MergePatchOperationFactory extends MergePatchOperationFactoryBase
{
    private $pageLabel;

    private $enum = [];

    public function __construct($pageLabel, $enum)
    {
        $this->pageLabel = $pageLabel;
        $this->enum = $enum;
        parent::__construct();
    }

    protected function generateOperationAdditionalProperties()
    {
        $properties = parent::generateOperationAdditionalProperties();
        $schema = new ReferenceSchema();
        $schema->type = 'string';
        $schema->enum = $this->enum;
        array_unshift(
            $properties['parameters'],
            new OA\Parameter($this->pageLabel, OA\Parameter::IN_PATH, 'Page identifier', [
                'schema' => $schema,
                'required' => true,
            ])
        );

        return $properties;
    }
}