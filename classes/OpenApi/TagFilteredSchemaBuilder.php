<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3 as OA;

class TagFilteredSchemaBuilder implements SchemaBuilderInterface
{
    private $realBuilder;

    private $tags;

    private $title;

    public function __construct(SchemaBuilderInterface $realBuilder, array $tags = [], ?string $title = null)
    {
        $this->realBuilder = $realBuilder;
        $this->tags = $tags;
        $this->title = $title;
    }

    public function build()
    {
        $schema = $this->realBuilder->build();
        if (empty($this->tags) || !$schema instanceof OA\Document) {
            return $schema;
        }

        $filteredSchema = clone $schema;
        if ($this->title){
            $filteredSchema->info->title = $this->title;
        }
        $filteredSchema->paths = [];
        $filteredSchema->tags = $this->tags;

        foreach ($schema->paths as $name => $path) {
            foreach ($path as $key => $value) {
                if (empty(array_diff($value['tags'], $this->tags))) {
                    $filteredSchema->paths[$name] = $path;
                    break;
                }
            }
        }

        return $filteredSchema;
    }

    public function getSettings()
    {
        return $this->realBuilder->getSettings();
    }

}