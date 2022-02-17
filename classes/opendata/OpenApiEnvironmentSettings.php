<?php

use Opencontent\OpenApi\EndpointFactory\NodeClassesEndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\OutOfRangeException;
use Opencontent\OpenApi\Exceptions\TranslationNotFoundException;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\OperationFactory\ContentObject\PublicationOptions as OpenApiPublicationOptions;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;
use Opencontent\OpenApi\SchemaFactory;
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

    const DISCRIMINATOR_SCHEMA_NAME = 'TypedResource';

    const DISCRIMINATED_SCHEMA_PREFIX = 'Typed';

    /**
     * @var bool
     */
    protected $debug = true;

    /**
     * @var int
     */
    private $parentNodeId;

    /**
     * @var ContentClassSchemaFactory[]
     */
    private $schemaFactories;

    /**
     * @var null|string
     */
    private $language;

    private $payloadAction = PayloadBuilder::UPDATE;

    private $endpointFactory;

    /**
     * OpenApiEnvironmentSettings constructor.
     * @param NodeClassesEndpointFactory $endpointFactory
     * @param SchemaFactory[] $schemaFactories
     * @param null $language
     * @throws InvalidParameterException
     * @throws \Opencontent\Opendata\Api\Exception\OutOfRangeException
     */
    public function __construct($endpointFactory, array $schemaFactories = [], $language = null)
    {
        $this->endpointFactory = $endpointFactory;
        $this->parentNodeId = $endpointFactory->getNodeId();
        $this->schemaFactories = $schemaFactories;
        $this->language = $language ? $language : eZContentObject::defaultLanguage();
        if (!isset(SchemaBuilderToolsTrait::getLanguageList()[$this->language])) {
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
        $payloadBuilder->setAction(PayloadBuilder::CREATE);
        $payloadBuilder->setClassIdentifier($schemaFactory->getClassIdentifier());
        $payloadBuilder->setParentNode($this->parentNodeId);
        $payloadBuilder->setLanguages([$this->language]);
        $schemaFactory->serializePayload($payloadBuilder, $data, $this->language);

        return ContentCreateStruct::fromArray($payloadBuilder->getArrayCopy());
    }

    private function discriminateSchemaFactory($data)
    {
        if (count($this->schemaFactories) > 1) {
            if (!isset($data[self::DISCRIMINATOR_PROPERTY_NAME])) {
                throw new InvalidPayloadException(self::DISCRIMINATOR_PROPERTY_NAME, 'value is required');
            }

            $schemaFactory = null;
            $discriminatorValue = $data[self::DISCRIMINATOR_PROPERTY_NAME];
            foreach ($this->schemaFactories as $schemaFactoryToDiscriminate) {
                if (self::DISCRIMINATED_SCHEMA_PREFIX . $schemaFactoryToDiscriminate->getName() == $discriminatorValue
                    || $schemaFactoryToDiscriminate->getName() == $discriminatorValue) {
                    $schemaFactory = $schemaFactoryToDiscriminate;
                }
            }

            if (!$schemaFactory) {
                throw new InvalidPayloadException(self::DISCRIMINATOR_PROPERTY_NAME, $discriminatorValue);
            }

        } else {
            $schemaFactory = $this->schemaFactories[0];
        }

        return $schemaFactory;
    }

    public function setIsPatch()
    {
        $this->payloadAction = PayloadBuilder::PATCH;
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

        $payloadBuilder->setAction($this->payloadAction);
        $payloadBuilder->setOption('update_null_field', $this->payloadAction == PayloadBuilder::UPDATE);

        $payloadBuilder->setClassIdentifier($schemaFactory->getClassIdentifier());
        $payloadBuilder->setParentNode($this->parentNodeId);
        $schemaFactory->serializePayload($payloadBuilder, $data, $this->language);

        $object = eZContentObject::fetch($data['_id']);
        if (!$object instanceof eZContentObject) {
            throw new NotFoundException($data['_id']);
        }

        $payloadArray = $payloadBuilder->getArrayCopy();

        // handle translations
        $allLanguages = array_keys($object->allLanguages());
        if (count($allLanguages) > 1 || $allLanguages[0] !== $this->language) {
            $content = Content::createFromEzContentObject($object);
            $currentLanguage = $this->language;
            $payloadBuilder->appendAction(PayloadBuilder::TRANSLATE);
            foreach ($allLanguages as $language) {
                if ($language != $currentLanguage) {
                    $this->language = $language;
                    $localizedPayload = $this->filterContent($content);
                    $schemaFactory->serializePayload($payloadBuilder, $localizedPayload, $this->language);
                }
            }
            if (!in_array($currentLanguage, $allLanguages)) {
                $allLanguages[] = $currentLanguage;
            }
            $payloadBuilder->removeAction(PayloadBuilder::TRANSLATE);
            $this->language = $currentLanguage;
        }
        $payloadBuilder->setLanguages($allLanguages);
        $payloadArray = $payloadBuilder->getArrayCopy();

        $this->payloadAction = PayloadBuilder::UPDATE;
        if (!isset($payloadArray['data'])){
            $payloadArray['data'] = array_fill_keys($allLanguages, []);
        }else{
            foreach ($allLanguages as $language){
                if (!isset($payloadArray['data'][$language])){
                    $payloadArray['data'][$language] = [];
                }
            }
        }

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
     * @throws TranslationNotFoundException
     */
    public function filterContent(Content $content)
    {
        $availableClasses = [];
        foreach ($this->schemaFactories as $schemaFactory) {
            $schemaFactory->setContextEndpoint($this->endpointFactory);
            $availableClasses[] = $schemaFactory->getClassIdentifier();
            if ($schemaFactory->getClassIdentifier() == $content->metadata->classIdentifier) {
                if (!isset($content->data[$this->language])) {
                    throw new TranslationNotFoundException($content->metadata->remoteId, $this->language);
                }
                $value = $schemaFactory->serializeValue($content, $this->language);
                if (count($this->schemaFactories) > 1){
                    $value[self::DISCRIMINATOR_PROPERTY_NAME] = self::DISCRIMINATED_SCHEMA_PREFIX . $schemaFactory->getName();
                }

                return $value;
            }
        }

        throw new OutOfRangeException($content->metadata->classIdentifier, implode(', ', $availableClasses));
    }

    /**
     * @param $contentId
     * @param ContentUpdateStruct $struct
     * @throws InvalidParameterException
     */
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
}
