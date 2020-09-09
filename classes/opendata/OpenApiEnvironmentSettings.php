<?php

use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\OutOfRangeException;
use Opencontent\OpenApi\Exceptions\TranslationNotFoundException;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\OperationFactory\ContentObject\PublicationOptions as OpenApiPublicationOptions;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;
use Opencontent\Opendata\Api\EnvironmentSettings;
use Opencontent\Opendata\Api\Exception\NotFoundException;
use Opencontent\Opendata\Api\Structs\ContentCreateStruct;
use Opencontent\Opendata\Api\Structs\ContentDataStruct;
use Opencontent\Opendata\Api\Structs\ContentUpdateStruct;
use Opencontent\Opendata\Api\Structs\MetadataStruct;
use Opencontent\Opendata\Api\Values\Content;

class OpenApiEnvironmentSettings extends EnvironmentSettings
{
    const DISCRIMINATOR_PROPERTY_NAME = 'resource_name';

    private $parentNodeId;

    private $schemaFactories;

    private $language;

    protected $debug = true;

    /**
     * OpenApiEnvironmentSettings constructor.
     * @param int $parentNodeId
     * @param ContentClassSchemaFactory[] $schemaFactories
     * @param null $language
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function __construct($parentNodeId, array $schemaFactories = [], $language = null)
    {
        $this->parentNodeId = $parentNodeId;
        $this->schemaFactories = $schemaFactories;
        $this->language = $language ? $language : eZContentObject::defaultLanguage();
        if (!in_array($this->language, \eZContentLanguage::fetchLocaleList())) {
            throw new InvalidParameterException("language", $this->language);
        }
        parent::__construct();
    }

    /**
     * @param $data
     * @return ContentCreateStruct
     * @throws InvalidPayloadException
     */
    public function instanceCreateStruct($data)
    {
        $schemaFactory = $this->discriminateSchemaFactory($data);

        $payloadBuilder = new PayloadBuilder();
        $payloadBuilder->action = PayloadBuilder::CREATE;
        $payloadBuilder->setClassIdentifier($schemaFactory->getClassIdentifier());
        $payloadBuilder->setParentNode($this->parentNodeId);
        $payloadBuilder->setLanguages([$this->language]);
        $schemaFactory->serializePayload($payloadBuilder, $data, $this->language);

        return ContentCreateStruct::fromArray($payloadBuilder->getArrayCopy());
    }

    /**
     * @param $data
     * @return ContentUpdateStruct
     * @throws InvalidPayloadException
     * @throws NotFoundException
     * @throws OutOfRangeException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     * @throws ezcBasePropertyNotFoundException
     * @throws ezcBaseValueException
     */
    public function instanceUpdateStruct($data)
    {
        $schemaFactory = $this->discriminateSchemaFactory($data);

        $payloadBuilder = new PayloadBuilder();

        $payloadBuilder->action = PayloadBuilder::UPDATE;
        $payloadBuilder->setOption('update_null_field', true); // is put not patch!

        $payloadBuilder->setClassIdentifier($schemaFactory->getClassIdentifier());
        $payloadBuilder->setParentNode($this->parentNodeId);
        $schemaFactory->serializePayload($payloadBuilder, $data, $this->language);

        $object = eZContentObject::fetch($data['_id']);
        if (!$object instanceof eZContentObject) {
            throw new NotFoundException($data['_id']);
        }

        // handle translations
        $allLanguages = array_keys($object->allLanguages());
        if (count($allLanguages) > 1 || $allLanguages[0] !== $this->language) {
            $content = Content::createFromEzContentObject($object);
            $currentLanguage = $this->language;
            $payloadBuilder->action = PayloadBuilder::TRANSLATE;
            foreach ($allLanguages as $language) {
                if ($language != $currentLanguage) {
                    $this->language = $language;
                    $localizedPayload = $this->filterContent($content);
                    $schemaFactory->serializePayload($payloadBuilder, $localizedPayload, $this->language);
                }
            }
            $payloadBuilder->action = PayloadBuilder::UPDATE;
            $this->language = $currentLanguage;
        }
        $payloadBuilder->setLanguages($allLanguages);
        $payloadArray = $payloadBuilder->getArrayCopy();

        return new ContentUpdateStruct(
            new MetadataStruct($payloadArray['metadata']),
            new ContentDataStruct($payloadArray['data']),
            new OpenApiPublicationOptions($payloadArray['options'])
        );
    }

    /**
     * @param Content $content
     * @return array
     * @throws OutOfRangeException
     */
    public function filterContent(Content $content)
    {
        $availableClasses = [];
        foreach ($this->schemaFactories as $schemaFactory) {
            $availableClasses[] = $schemaFactory->getClassIdentifier();
            if ($schemaFactory->getClassIdentifier() == $content->metadata->classIdentifier) {
                if (!isset($content->data[$this->language])){
                    throw new TranslationNotFoundException($content->metadata->remoteId, $this->language);
                }
                return $schemaFactory->serializeValue($content, $this->language);
            }
        }

        throw new OutOfRangeException($content->metadata->classIdentifier, implode(', ', $availableClasses));
    }

    public function afterUpdate($contentId, ContentUpdateStruct $struct)
    {
        if ($struct->options->update_remote_id) {
            $object = eZContentObject::fetch($contentId);
            if ($object instanceof eZContentObject) {
                if ($object->attribute('remote_id') !== $struct->options->update_remote_id) {
                    $alreadyExists = \eZContentObject::fetchByRemoteID($struct->options->update_remote_id);
                    if ($alreadyExists instanceof eZContentObject && (int)$alreadyExists->attribute('id') !== (int)$struct->metadata->id) {
                        throw new InvalidParameterException('id', $struct->options->update_remote_id);
                    }
                }
                if ($struct->options->update_remote_id != $object->attribute('remote_id')) {
                    $object->setAttribute('remote_id', $struct->options->update_remote_id);
                    $object->setAttribute('modified', (int)$object->attribute('modified') + 1);
                    $object->store();
                    eZSearch::addObject($object);
                }
            }
        }
    }

    private function discriminateSchemaFactory($data)
    {
        if (count($this->schemaFactories) > 1) {
            if (!isset($data[self::DISCRIMINATOR_PROPERTY_NAME])){
                throw new InvalidPayloadException(self::DISCRIMINATOR_PROPERTY_NAME, 'value is required');
            }

            $schemaFactory = null;
            $discriminatorValue = $data[self::DISCRIMINATOR_PROPERTY_NAME];
            foreach ($this->schemaFactories as $schemaFactoryToDiscriminate){
                if ($schemaFactoryToDiscriminate->getName() == $discriminatorValue){
                    $schemaFactory = $schemaFactoryToDiscriminate;
                }
            }

            if (!$schemaFactory){
                throw new InvalidPayloadException(self::DISCRIMINATOR_PROPERTY_NAME, $discriminatorValue);
            }

        } else {
            $schemaFactory = $this->schemaFactories[0];
        }

        return $schemaFactory;
    }
}