<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
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
        if (is_array($content)) {
            foreach ($content as $item) {
                if (!empty($item['filename'])) {
                    $data[] = [
                        'filename' => $item['filename'],
                        'uri' => $item['url'],
                    ];
                }
            }
        }

        return $data;
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        if (isset($payload[$this->providePropertyIdentifier()])){
            $value = $payload[$this->providePropertyIdentifier()];
            $normalizedValue = [];
            foreach ($value as $item){
                if (isset($item['uri'])) {
                    $item = $this->getPayloadFileData($item);
                }
                $normalizedValue[] = $item;
            }
            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                $normalizedValue
            );
        }
    }

    private function getPayloadFileData($item)
    {
        $fileUri = $item['uri'];
        if (!\eZHTTPTool::getDataByURL(trim($fileUri), true)) {
            $parts = explode('/', $fileUri);
            $filename = array_pop($parts);
            array_pop($parts);
            $originalFilename = array_pop($parts);
            $version = array_pop($parts);
            $attributeId = array_pop($parts);

            $binaryFile = \eZPersistentObject::fetchObject(\eZMultiBinaryFile::definition(),
                null,
                array(
                    'contentobject_attribute_id' => (int)$attributeId,
                    'version' => (int)$version,
                    'filename' => $originalFilename
                )
            );
            if ($binaryFile instanceof \eZMultiBinaryFile) {

                $fileHandler = \eZClusterFileHandler::instance($binaryFile->attribute('filepath'));
                return [
                    'filename' => $item['filename'],
                    'file' => base64_encode($fileHandler->fetchContents())
                ];
            }
        }

        return [
            'filename' => $item['filename'],
            'url' => $fileUri
        ];
    }
}
