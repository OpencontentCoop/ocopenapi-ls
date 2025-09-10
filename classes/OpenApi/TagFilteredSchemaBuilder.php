<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaBuilder\Operation;

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
        array_unshift($this->tags, 'status');
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
        $filteredSchema->tags = [];
        foreach ($this->tags as $tag) {
            $filteredSchema->tags[] = new OA\Tag($tag);
        }

        $filteredRefs = ['#/components/schemas/TypedResource'];
        foreach ($schema->paths as $name => $path) {
            $path = json_decode(json_encode($path), true);
            if ($this->readOnly) {
                unset($path['post']);
                unset($path['put']);
                unset($path['patch']);
                unset($path['delete']);
            }

            $pathTags = array_column($path, 'tags');
            $pathTags = array_unique(array_merge(...$pathTags));

            if ($this->flattenTag) {
                $path['post']['tags'] = [];
                $path['put']['tags'] = [];
                $path['patch']['tags'] = [];
                $path['delete']['tags'] = [];
                $path['get']['tags'] = [];
            }

            foreach ($path as $key => $value) {
                if (empty(array_diff($pathTags, $this->tags))) {
                    $filteredRefs = array_merge($filteredRefs, $this->extractSchemas(var_export($path,1)));
                    $additionalProperties = $value;
                    unset($additionalProperties['responses']);
                    unset($additionalProperties['operationId']);
                    unset($additionalProperties['summary']);
                    $filteredSchema->paths[$name][$key] = new Operation(
                        $value['responses'],
                        $value['operationId'],
                        $value['summary'],
                        $additionalProperties
                    );;
                }
            }
        }

        $filteredRefs = array_unique($filteredRefs);
        $schemas = $filteredSchema->components['schemas'];
        foreach ($schemas as $name => $schema) {
            if (in_array('#/components/schemas/' . $name, $filteredRefs)
                || in_array('#/components/schemas/Typed' . $name, $filteredRefs)) {
                $filteredRefs = array_merge($filteredRefs, $this->extractSchemas(var_export($schema,1)));
            }
        }
        $filteredRefs = array_unique($filteredRefs);
        foreach ($schemas as $name => $schema) {
            if (!in_array('#/components/schemas/' . $name, $filteredRefs)
                && !in_array('#/components/schemas/Typed' . $name, $filteredRefs)) {
                unset($schemas[$name]);
            }
        }
        $filteredSchema->components['schemas'] = $schemas;

        foreach ($filteredSchema->components['requestBodies'] as $name => $schema) {
            if (!in_array('#/components/schemas/' . $name, $filteredRefs)){
                unset($filteredSchema->components['requestBodies'][$name]);
            }
        }

        if ($this->flattenTag) {
            $filteredSchema->tags = [];
        }

        return $filteredSchema;
    }

    private function extractSchemas(string $data): array
    {
        $pattern = '/#\/components\/schemas\/[A-Za-z0-9_\-]+/';
        preg_match_all($pattern, $data, $matches);
        return $matches[0] ?? [];
    }

    public function getSettings()
    {
        return $this->realBuilder->getSettings();
    }
}