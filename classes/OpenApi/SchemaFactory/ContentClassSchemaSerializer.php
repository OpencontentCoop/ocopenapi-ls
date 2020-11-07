<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use eZContentClass;
use eZContentClassAttribute;
use eZINI;
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

    private static $factories = [];

    private static $metas = [];

    private static $properties = [];

    public function generateSchema($classIdentifier, $schemaName)
    {
        if (!isset(self::$schemas[$classIdentifier])) {
            $class = $this->loadClass($classIdentifier);
            $factories = $this->loadFactories($class, $schemaName);
            $properties = [];
            foreach ($factories as $identifier => $factory) {
                $properties[$identifier] = $factory->provideProperties();
            }
            $requiredFields = [];
            foreach ($factories as $identifier => $factory) {
                if ($factory->isRequired()) {
                    $requiredFields[] = $identifier;
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
     * @param string $schemaName
     * @return ContentClassAttributePropertyFactory[]
     */
    private function loadFactories(eZContentClass $class, $schemaName)
    {
        if (!isset(self::$factories[$class->attribute('identifier')][$schemaName])) {
            self::$factories[$class->attribute('identifier')][$schemaName] = [];

            $metaFields = [
                'remoteId',
                'uri',
                'published',
                'modified',
            ];

            if (eZINI::instance('ocopenapi.ini')->hasVariable('ContentMetaSettings', 'ContentMetaPropertyFactories')) {
                $keys = array_keys(eZINI::instance('ocopenapi.ini')->variable('ContentMetaSettings', 'ContentMetaPropertyFactories'));
                $classIdentifier = $class->attribute('identifier');
                foreach ($keys as $field) {
                    $metaFields[] = str_replace($classIdentifier . '/', '', $field);
                }
            }

            foreach ($metaFields as $identifier) {
                $factory = self::loadContentMetaPropertyFactory($class, $identifier);
                if ($factory instanceof ContentMetaPropertyFactory) {
                    self::$factories[$class->attribute('identifier')][$schemaName][$factory->providePropertyIdentifier()] = $factory;
                }
            }

            foreach ($class->dataMap() as $identifier => $attribute) {
                $factory = self::loadContentClassAttributePropertyFactory($class, $attribute);
                if ($factory instanceof ContentClassAttributePropertyFactory) {
                    self::$factories[$class->attribute('identifier')][$schemaName][$factory->providePropertyIdentifier()] = $factory;
                }
            }
        }

        return self::$factories[$class->attribute('identifier')][$schemaName];
    }

    /**
     * @param eZContentClass $class
     * @param $identifier
     * @return ContentMetaPropertyFactory|false
     */
    public static function loadContentMetaPropertyFactory($class, $identifier)
    {
        if (!isset(self::$metas[$class->attribute('id') . $identifier])) {

            self::$metas[$class->attribute('id') . $identifier] = false;

            $settings = [];
            if (eZINI::instance('ocopenapi.ini')->hasGroup('ContentMetaSettings')) {
                $settings = eZINI::instance('ocopenapi.ini')->group('ContentMetaSettings');
            }

            $customMetaPropertyFactory = 'null';

            $classIdentifier = $class->attribute('identifier');

            if (isset($settings['ContentMetaPropertyFactories'][$classIdentifier . '/' . $identifier])) {
                $customMetaPropertyFactory = $settings['ContentMetaPropertyFactories'][$classIdentifier . '/' . $identifier];
            } elseif (isset($settings['ContentMetaPropertyFactories'][$identifier])) {
                $customMetaPropertyFactory = $settings['ContentMetaPropertyFactories'][$identifier];
            } else {

                $defaults = [
                    'remoteId' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\RemoteIdFactoryProvider',
                    'uri' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\UriFactoryProvider',
                    'published' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\PublishedFactoryProvider',
                    'modified' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\ModifiedFactoryProvider',
                ];

                if (isset($defaults[$identifier])) {
                    $customMetaPropertyFactory = $defaults[$identifier];
                }
            }

            if (class_exists($customMetaPropertyFactory)) {
                self::$metas[$class->attribute('id') . $identifier] = new $customMetaPropertyFactory($class, $identifier);
            } else {
                Logger::instance()->error("ContentMetaPropertyFactory not found", ['identifier' => $identifier, 'method' => __METHOD__]);
                $fallbackFactory = new ContentMetaPropertyFactory($class, $identifier);
                if ($fallbackFactory->isRequired()) {
                    self::$metas[$class->attribute('id') . $identifier] = $fallbackFactory;
                }
            }
        }

        return self::$metas[$class->attribute('id') . $identifier];
    }

    /**
     * @param eZContentClass $class
     * @param eZContentClassAttribute $attribute
     * @return ContentClassAttributePropertyFactory|false
     */
    public static function loadContentClassAttributePropertyFactory($class, $attribute)
    {
        if (!isset(self::$properties[$attribute->attribute('id')])) {
            self::$properties[$attribute->attribute('id')] = false;

            $settings = [];
            if (eZINI::instance('ocopenapi.ini')->hasGroup('ClassAttributeSettings')) {
                $settings = eZINI::instance('ocopenapi.ini')->group('ClassAttributeSettings');
            }

            $customAttributePropertyFactory = 'null';

            $classIdentifier = $class->attribute('identifier');
            $identifier = $attribute->attribute('identifier');
            $dataType = $attribute->attribute('data_type_string');

            if (isset($settings['ClassAttributePropertyFactories'][$classIdentifier . '/' . $identifier])) {
                $customAttributePropertyFactory = $settings['ClassAttributePropertyFactories'][$classIdentifier . '/' . $identifier];
            } elseif (isset($settings['ClassAttributePropertyFactories'][$identifier])) {
                $customAttributePropertyFactory = $settings['ClassAttributePropertyFactories'][$identifier];
            } elseif (isset($settings['ClassAttributePropertyFactories'][$dataType])) {
                $customAttributePropertyFactory = $settings['ClassAttributePropertyFactories'][$dataType];
            } else {

                $defaults = [
                    'ezselection' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\SelectionFactoryProvider',
                    'ezprice' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\PriceFactoryProvider',
                    'ezkeyword' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\KeywordsFactoryProvider',
                    'eztags' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\TagsFactoryProvider',
                    'ezgmaplocation' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\GeoFactoryProvider',
                    'ezdate' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\DateFactoryProvider',
                    'ezdatetime' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\DateTimeFactoryProvider',
                    'eztime' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\TimeFactoryProvider',
                    'ezmatrix' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\MatrixFactoryProvider',
                    'ezxmltext' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\EzXmlFactoryProvider',
                    'ezauthor' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\AuthorFactoryProvider',
                    'ezobjectrelation' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\RelationFactoryProvider',
                    'ezobjectrelationlist' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\RelationsFactoryProvider',
                    'ezbinaryfile' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\FileFactoryProvider',
                    'ezimage' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\ImageFactoryProvider',
                    //'ezpage' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\PageFactoryProvider',
                    'ezboolean' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\BooleanFactoryProvider',
                    'ezuser' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\UserFactoryProvider',
                    'ezfloat' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\FloatFactoryProvider',
                    'ezinteger' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\IntegerFactoryProvider',
                    'ezstring' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\StringFactoryProvider',
                    'ezsrrating' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\RatingFactoryProvider',
                    'ezemail' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\EmailFactoryProvider',
                    'ezcountry' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\CountryFactoryProvider',
                    'ezurl' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\UrlFactoryProvider',
                    'eztext' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\TextFactoryProvider',
                    'ocmultibinary' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\MultiFileFactoryProvider',
                    'ezmedia' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\MediaFactoryProvider',
                    'ocevent' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\EventFactoryProvider',
                    'ocgdpr' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\GdprFactoryProvider',
                    'openpabootstrapitaliaicon' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\BootstrapItaliaIconFactoryProvider',

                    // ignore
                    'openparole' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
                    'openpareverserelationlist' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
                    'ezpage' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
                    'ezpaex' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
                    'occhart' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
                ];

                if (isset($defaults[$dataType])) {
                    $customAttributePropertyFactory = $defaults[$dataType];
                }
            }

            if (class_exists($customAttributePropertyFactory)) {
                self::$properties[$attribute->attribute('id')] = new $customAttributePropertyFactory($class, $attribute);
            } else {
                Logger::instance()->error("ContentClassAttributePropertyFactory not found", [
                    'datatype' => $dataType,
                    'class' => $classIdentifier,
                    'attribute' => $identifier,
                    'method' => __METHOD__
                ]);
                $fallbackFactory = new ContentClassAttributePropertyFactory($class, $attribute);
                if ($fallbackFactory->isRequired()) {
                    self::$properties[$attribute->attribute('id')] = $fallbackFactory;
                }
            }
        }
        return self::$properties[$attribute->attribute('id')];
    }

    public function serializeValue($classIdentifier, $schemaName, Content $content, $locale)
    {
        $class = $this->loadClass($classIdentifier);
        $factories = $this->loadFactories($class, $schemaName);
        $value = [];
        foreach ($factories as $identifier => $factory) {
            $value[$identifier] = $factory->serializeValue($content, $locale);
        }

        return $value;
    }

    public function serializePayload($classIdentifier, $schemaName, PayloadBuilder $payloadBuilder, array $payload, $locale)
    {
        $class = $this->loadClass($classIdentifier);
        $factories = $this->loadFactories($class, $schemaName);
        $errors = [];
        foreach ($factories as $identifier => $factory) {
            try {
                if ($factory->isRequired()
                    && !isset($payload[$factory->providePropertyIdentifier()])
                    && $payloadBuilder->action !== PayloadBuilder::PATCH) {
                    throw new InvalidPayloadException($factory->providePropertyIdentifier(), 'field is required');
                }
                if ($factory->isRequired()
                    && array_key_exists($factory->providePropertyIdentifier(), $payload)
                    && ($payload[$factory->providePropertyIdentifier()] === null
                        || $payload[$factory->providePropertyIdentifier()] === ''
                        || $payload[$factory->providePropertyIdentifier()] === [])
                ) {
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
}