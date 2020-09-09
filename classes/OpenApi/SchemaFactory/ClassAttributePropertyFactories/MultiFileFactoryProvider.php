<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class MultiFileFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return [
            "type" => "array",
            "description" => $this->getPropertyDescription(),
            "items" => [
                "type" => "object",
                "properties" => [
                    "filename" => [
                        "title" => 'File name',
                        "type" => "string"
                    ],
                    "file" => [
                        "title" => "Base64-encoded file contents",
                        "type" => "string",
                        "format" => "byte",
                        "writeOnly" => true
                    ],
                    "uri" => [
                        "title" => 'File uri',
                        "type" => "string",
                        "readOnly" => true
                    ],
                ]
            ]
        ];
    }

    public function serializeValue(Content $content, $locale)
    {
        $content = $this->getContent($content, $locale);
        $data = [];
        foreach ($content as $item) {
            $data[] = [
                'filename' => $content['filename'],
                'uri' => $content['url'],
            ];
        }

        return $data;
    }
}