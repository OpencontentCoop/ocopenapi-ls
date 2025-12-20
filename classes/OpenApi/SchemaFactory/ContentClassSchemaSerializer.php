<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use eZContentClass;
use eZContentClassAttribute;
use eZINI;
use Opencontent\OpenApi\EndpointFactory\NodeClassesEndpointFactory;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Logger;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;
use Opencontent\Opendata\Api\Values\Content;
use RuntimeException;

class ContentClassSchemaSerializer
{
    use SchemaBuilderToolsTrait;

    private static $classes = [];

    private static $schemas = [];

    private static $factoriesLoaders = [];

    /**
     * @var NodeClassesEndpointFactory
     */
    protected $contextEndpoint;

    /**
     * @return NodeClassesEndpointFactory
     */
    public function getContextEndpoint()
    {
        return $this->contextEndpoint;
    }

    /**
     * @param NodeClassesEndpointFactory $contextEndpoint
     */
    public function setContextEndpoint($contextEndpoint)
    {
        $this->contextEndpoint = $contextEndpoint;
    }

    public function generateSchema($classIdentifier, $schemaName)
    {
        if (!isset(self::$schemas[$classIdentifier])) {
            $class = $this->loadClass($classIdentifier);
            $factories = static::getFactoriesLoader($class)->loadFactories();
            $properties = [];
            $deprecateCategories = [];
            if (eZINI::instance('ocopenapi.ini')->hasVariable('ClassAttributeSettings', 'DeprecateAttributeCategories')) {
                $deprecateCategories = (array)eZINI::instance('ocopenapi.ini')->variable('ClassAttributeSettings', 'DeprecateAttributeCategories');
            }
            $deprecateIdentifiers = [];
            if (eZINI::instance('ocopenapi.ini')->hasVariable('ClassAttributeSettings', 'DeprecateAttributeIdentifiers')) {
                $deprecateIdentifiers = (array)eZINI::instance('ocopenapi.ini')->variable('ClassAttributeSettings', 'DeprecateAttributeIdentifiers');
            }
            foreach ($factories as $identifier => $factory) {
                $properties[$identifier] = $factory->provideProperties();
                if ($factory instanceof ContentClassAttributePropertyFactory){
                    if (
                        in_array($factory->getAttribute()->attribute('category'), $deprecateCategories)
                        || in_array(
                            $factory->getClass()->attribute('identifier') . '/' . $factory->getAttribute()->attribute('identifier'),
                            $deprecateIdentifiers
                        )
                    ){
                        $properties[$identifier]['deprecated'] = true;
                    }
                }
            }

            $requiredFields = [];
            foreach ($factories as $identifier => $factory) {
                if ($factory->isRequired()) {
                    $requiredFields[] = $identifier;
                    unset($properties[$identifier]['nullable']);
                }
            }

            $schema = new OA\Schema();
            $schema->title = $schemaName;
            $schema->type = 'object';
            $schema->properties = [];
            foreach ($properties as $identifier => $values) {
                $schema->properties[$identifier] = $this->generateSchemaProperty($values);
            }
            if (count($requiredFields) > 0) {
                $schema->required = $requiredFields;
            }

            self::$schemas[$classIdentifier] = $schema;
        }

        return self::$schemas[$classIdentifier];
    }

    private function loadClass($classIdentifier)
    {
        if (!isset(self::$classes[$classIdentifier])) {
            self::$classes[$classIdentifier] = eZContentClass::fetchByIdentifier($classIdentifier);
            if (!self::$classes[$classIdentifier] instanceof eZContentClass) {
                throw new RuntimeException("$classIdentifier not found");
            }
        }

        return self::$classes[$classIdentifier];
    }

    /**
     * @param eZContentClass $class
     * @param $identifier
     * @return ContentMetaPropertyFactory|false
     */
    public static function loadContentMetaPropertyFactory($class, $identifier)
    {
        return static::getFactoriesLoader($class)->loadContentMetaPropertyFactory($identifier);
    }

    /**
     * @param eZContentClass $class
     * @param eZContentClassAttribute $attribute
     * @return ContentClassAttributePropertyFactory|false
     */
    public static function loadContentClassAttributePropertyFactory($class, $attribute)
    {
        return static::getFactoriesLoader($class)->loadContentClassAttributePropertyFactory($attribute);
    }

    public function serializeValue($classIdentifier, $schemaName, Content $content, $locale)
    {
        $class = $this->loadClass($classIdentifier);
        $factories = static::getFactoriesLoader($class)->loadFactories();
        $value = [];
        foreach ($factories as $identifier => $factory) {
            $factory->setContextEndpoint($this->getContextEndpoint());
            $value[$identifier] = $factory->serializeValue($content, $locale);
        }

        return $value;
    }

    public function serializePayload($classIdentifier, $schemaName, PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        $class = $this->loadClass($classIdentifier);
        $factories = static::getFactoriesLoader($class)->loadFactories();
        $errors = [];
        foreach ($factories as $identifier => $factory) {
            if ($factory instanceof ContentMetaPropertyFactory && $payloadBuilder->isAction(PayloadBuilder::TRANSLATE)){
                continue;
            }
            try {
                if ($factory->isRequired()
                    && !isset($payload[$factory->providePropertyIdentifier()])
                    && !$payloadBuilder->isAction(PayloadBuilder::PATCH)) {
                    throw new InvalidPayloadException($factory->providePropertyIdentifier(), 'field is required');
                }
                $isEmpty = (
                    array_key_exists($factory->providePropertyIdentifier(), $payload)
                    && ($payload[$factory->providePropertyIdentifier()] === null
                        || $payload[$factory->providePropertyIdentifier()] === ''
                        || $payload[$factory->providePropertyIdentifier()] === [])
                );
                if ($isEmpty && $payloadBuilder->isAction(PayloadBuilder::TRANSLATE)){
                    continue;
                }
                if ($factory->isRequired() && $isEmpty) {
                    throw new InvalidPayloadException($factory->providePropertyIdentifier(), 'field can not be empty');
                }
                $factory->serializePayload($payloadBuilder, $payload, $locale);
            } catch (InvalidPayloadException $e) {
                $errors[] = $e->getMessage();
            }
        }
        if (count($errors) > 0) {
            throw new InvalidPayloadException(implode(', ', $errors));
        }
    }

    protected static function getFactoriesLoader(eZContentClass $class): ContentClassSchemaSerializerFactoriesLoader
    {
        if (!isset(self::$factoriesLoaders[$class->attribute('identifier')])) {
            $metaProperties = [
                'remoteId' => null,
                'uri' => null,
                'published' => null,
                'modified' => null,
            ];

            if (eZINI::instance('ocopenapi.ini')->hasVariable('ContentMetaSettings', 'ContentMetaPropertyFactories')) {
                $keys = array_keys(
                    eZINI::instance('ocopenapi.ini')->variable('ContentMetaSettings', 'ContentMetaPropertyFactories')
                );
                $classIdentifier = $class->attribute('identifier');
                foreach ($keys as $field) {
                    if (strpos($field, '/') !== false) {
                        if (strpos($field, $class->attribute('identifier') . '/') !== false) {
                            $metaProperties[] = str_replace($classIdentifier . '/', '', $field);
                        }
                    } else {
                        $metaProperties[$field] = null;
                    }
                }
            }
            self::$factoriesLoaders[$class->attribute('identifier')] = new ContentClassSchemaSerializerFactoriesLoader(
                $class, $metaProperties
            );
        }
        return self::$factoriesLoaders[$class->attribute('identifier')];
    }
}
