<?php

namespace Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;

use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\Opendata\Api\Values\Content;

class MultiFileFactoryProvider extends ContentClassAttributePropertyFactory
{
    /**
     * Proprieta' esposte dall'api => chiavi usate dal convertitore opendata (OCMultiBinaryOpendataConverter),
     * disponibili solo se l'attributo di classe ha attive le decorazioni (OCMultiBinaryType::ALLOW_DECORATIONS_FIELD)
     */
    private const DECORATION_FIELDS = [
        'display_name' => 'displayName',
        'display_group' => 'group',
        'display_text' => 'text',
    ];

    public function provideProperties()
    {
        $properties = [
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
        ];

        if ($this->hasDecorations()) {
            $properties["display_name"] = [
                "title" => 'Display name',
                "type" => "string"
            ];
            $properties["display_group"] = [
                "title" => 'Display group',
                "type" => "string"
            ];
            $properties["display_text"] = [
                "title" => 'Display text',
                "type" => "string"
            ];
        }

        return [
            "type" => "array",
            "description" => $this->getPropertyDescription(),
            "items" => [
                "type" => "object",
                "properties" => $properties
            ]
        ];
    }

    private function hasDecorations(): bool
    {
        return (bool)$this->attribute->attribute(\OCMultiBinaryType::ALLOW_DECORATIONS_FIELD);
    }

    public function serializeValue(Content $content, $locale)
    {
        $content = $this->getContent($content, $locale);
        $data = [];
        if (is_array($content)) {
            foreach ($content as $item) {
                if (!empty($item['filename'])) {
                    $fileData = [
                        'filename' => $item['filename'],
                        'uri' => $item['url'],
                    ];
                    if ($this->hasDecorations()) {
                        foreach (self::DECORATION_FIELDS as $apiName => $opendataName) {
                            if (isset($item[$opendataName]) && $item[$opendataName] !== '') {
                                $fileData[$apiName] = $item[$opendataName];
                            }
                        }
                    }
                    $data[] = $fileData;
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
                $decorations = $this->getPayloadDecorations($item);
                $item = array_diff_key($item, self::DECORATION_FIELDS);
                if (isset($item['uri'])) {
                    $item = $this->getPayloadFileData($item);
                }
                $normalizedValue[] = $item + $decorations;
            }
            $payloadBuilder->setData(
                $locale,
                $this->attribute->attribute('identifier'),
                $normalizedValue
            );
        }
    }

    /**
     * Converte le proprieta' di decorazione dell'api nelle chiavi attese dal convertitore opendata
     */
    private function getPayloadDecorations(array $item): array
    {
        $decorations = [];
        if ($this->hasDecorations()) {
            foreach (self::DECORATION_FIELDS as $apiName => $opendataName) {
                if (isset($item[$apiName])) {
                    $decorations[$opendataName] = $item[$apiName];
                }
            }
        }

        return $decorations;
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
