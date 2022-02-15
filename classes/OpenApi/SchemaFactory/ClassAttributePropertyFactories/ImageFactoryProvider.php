<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class ImageFactoryProvider extends ContentClassAttributePropertyFactory
{
    public function provideProperties()
    {
        return [
            "type" => "object",
            "description" => $this->getPropertyDescription(),
            "properties" => array(
                "filename" => array(
                    "title" => 'File name',
                    "type" => "string"
                ),
                "file" => array(
                    "title" => "Base64-encoded file contents",
                    "type" => "string",
                    "format" => "byte",
                    "writeOnly" => true
                ),
                "uri" => array(
                    "title" => 'File uri',
                    "type" => "string",
                    "readOnly" => true
                ),
            )
        ];
    }

    public function serializeValue(Content $content, $locale)
    {
        $content = $this->getContent($content, $locale);

        if (empty($content['filename'])) return null;

        return [
            'filename' => $content['filename'],
            'uri' => $content['url'],
        ];
    }
}
