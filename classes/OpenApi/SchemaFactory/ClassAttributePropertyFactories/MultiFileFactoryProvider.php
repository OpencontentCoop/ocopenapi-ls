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
                "description" => 'The file name is returned URL-encoded as stored (e.g. "a+b+%281%29.pdf"): when sending it again as a new file, send the decoded name; the encoded form is the one to use in the path of the single file endpoints',
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
                    $item = $this->getStoredFileData($payloadBuilder, $item) ?? $this->getPayloadFileData($item);
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

    /**
     * Un file gia' presente nel campo dello stesso oggetto viene riagganciato con l'identificatore "stored_"
     * (come fa la form del portale) invece di essere riscaricato dal suo uri e salvato di nuovo: evita di
     * ricodificare il nome del file a ogni scrittura (original_filename e' salvato con urlencode()) e un
     * download per ogni file esistente a ogni scrittura.
     *
     * Restituisce null se il file non appartiene a questo campo di questo oggetto (ad esempio un uri esterno
     * o di un altro campo): in quel caso OCMultiBinaryType::fromString() ignorerebbe l'identificatore.
     */
    private function getStoredFileData(PayloadBuilder $payloadBuilder, array $item): ?array
    {
        if (!preg_match('#ocmultibinary/download/(\d+)/(\d+)/(\d+)/([^/]+)/file/#', (string)$item['uri'], $matches)) {
            return null;
        }
        [, $objectId, $attributeId, $version, $storedFilename] = $matches;

        $metadata = isset($payloadBuilder['metadata']) ? (array)$payloadBuilder['metadata'] : [];
        $targetObjectId = null;
        if (!empty($metadata['id'])) {
            $targetObjectId = (int)$metadata['id'];
        } elseif (!empty($metadata['remoteId'])) {
            $targetObject = \eZContentObject::fetchByRemoteID($metadata['remoteId']);
            if ($targetObject instanceof \eZContentObject) {
                $targetObjectId = (int)$targetObject->attribute('id');
            }
        }
        if ($targetObjectId === null || $targetObjectId !== (int)$objectId) {
            return null;
        }

        $objectAttribute = \eZContentObjectAttribute::fetch((int)$attributeId, (int)$version);
        if (!$objectAttribute instanceof \eZContentObjectAttribute
            || (int)$objectAttribute->attribute('contentobject_id') !== $targetObjectId
            || (int)$objectAttribute->attribute('contentclassattribute_id') !== (int)$this->attribute->attribute('id')
        ) {
            return null;
        }

        $binaryFile = \eZPersistentObject::fetchObject(
            \eZMultiBinaryFile::definition(),
            null,
            [
                'contentobject_attribute_id' => (int)$attributeId,
                'version' => (int)$version,
                'filename' => urldecode($storedFilename),
            ]
        );
        if (!$binaryFile instanceof \eZMultiBinaryFile) {
            return null;
        }

        return [
            'stored' => \OCMultiBinaryType::generateAlreadyStoredFileId($objectAttribute, $binaryFile),
            'filename' => $binaryFile->attribute('original_filename'),
        ];
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
