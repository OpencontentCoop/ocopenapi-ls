<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3 as OA;

class TagFilteredSchemaBuilder implements SchemaBuilderInterface
{
    private $realBuilder;

    private $tags;

    private $title;

    private $readOnly;

    private $flattenTag;

    public function __construct(
        SchemaBuilderInterface $realBuilder,
        array $tags = [],
        ?string $title = null,
        bool $readOnly = false,
        bool $flattenTag = false
    ) {
        $this->realBuilder = $realBuilder;
        $this->tags = $tags;
        $this->title = $title;
        $this->readOnly = $readOnly;
        $this->flattenTag = $flattenTag;
    }

    public function build()
    {
        $schema = $this->realBuilder->build();
        if (empty($this->tags) || !$schema instanceof OA\Document) {
            return $schema;
        }

        $filteredSchema = clone $schema;
        if ($this->title) {
            $filteredSchema->info->title = $this->title;
        }
        $filteredSchema->paths = [];
        $filteredSchema->tags = $this->tags;

        $filteredRefs = ['#/components/schemas/TypedResource'];
        foreach ($schema->paths as $name => $path) {
            foreach ($path as $key => $value) {
                if (empty(array_diff($value['tags'], $this->tags))) {

                    if ($this->flattenTag) {
                        $path['post']['tags'] = [];
                        $path['put']['tags'] = [];
                        $path['patch']['tags'] = [];
                        $path['delete']['tags'] = [];
                        $path['get']['tags'] = [];
                    }

                    if ($this->readOnly) {
                        unset($path['post']);
                        unset($path['put']);
                        unset($path['patch']);
                        unset($path['delete']);
                    }

                    if (isset($path['get']['responses'][200]['content']['application/json']['schema']['$ref'])) {
                        $filteredRefs[] = $path['get']['responses'][200]['content']['application/json']['schema']['$ref'];
                    }
                    if (isset($path['get']['responses'][200]['content']['application/json']['schema']['oneOf'])) {
                        foreach ($path['get']['responses'][200]['content']['application/json']['schema']['oneOf'] as $oneOf) {
                            if (isset($oneOf['$ref'])) {
                                $filteredRefs[] = $oneOf['$ref'];
                            }
                        }
                    }
                    if (isset($path['get']['responses'][200]['content']['application/json']['schema']['properties']['items']['items']['$ref'])) {
                        $filteredRefs[] = $path['get']['responses'][200]['content']['application/json']['schema']['properties']['items']['items']['$ref'];
                    }

                    $filteredSchema->paths[$name] = $path;
                    break;
                }
            }
        }

        $filteredRefs = array_unique($filteredRefs);
        $schemas = $filteredSchema->components['schemas'];

        foreach ($schemas as $name => $schema) {
            if (!in_array('#/components/schemas/' . $name, $filteredRefs)
                && !in_array('#/components/schemas/Typed' . $name, $filteredRefs)) {
                unset($schemas[$name]);
            }
        }
        $filteredSchema->components['schemas'] = $schemas;

        if ($this->flattenTag) {
            $filteredSchema->tags = [];
        }

        return $filteredSchema;
    }

    public function getSettings()
    {
        return $this->realBuilder->getSettings();
    }
}