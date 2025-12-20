<?php

namespace Opencontent\OpenApi\SchemaFactory;

use eZContentClass;
use eZContentClassAttribute;
use eZINI;
use Opencontent\OpenApi\Logger;

class ContentClassSchemaSerializerFactoriesLoader
{
    protected $metaDefaults = [
        'remoteId' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\RemoteIdFactoryProvider',
        'uri' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\UriFactoryProvider',
        'published' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\PublishedFactoryProvider',
        'modified' => '\Opencontent\OpenApi\SchemaFactory\ContentMetaPropertyFactory\ModifiedFactoryProvider',
    ];

    protected $datatypeDefaults = [
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
        'ezidentifier' => '\Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory\IdentifierFactoryProvider',

        // ignore
        'openparole' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
        'openpareverserelationlist' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
        'ezpage' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
        'ezpaex' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
        'occhart' => '\Opencontent\OpenApi\SchemaFactory\NullSchemaFactory',
    ];

    private $metaProperties;

    /**
     * @var eZContentClass
     */
    private $contentClass;

    public function __construct(eZContentClass $contentClass, array $metaProperties = [])
    {
        $this->contentClass = $contentClass;
        $this->metaProperties = $metaProperties;
    }

    public function loadFactories(): array
    {
        $factories = [];

        foreach ($this->metaProperties as $identifier => $factory) {
            if (!$factory instanceof ContentMetaPropertyFactory) {
                $factory = $this->loadContentMetaPropertyFactory($identifier);
            }
            if ($factory instanceof ContentMetaPropertyFactory) {
                $factories[$factory->providePropertyIdentifier()] = $factory;
            }
        }

        foreach ($this->contentClass->dataMap() as $attribute) {
            $factory = $this->loadContentClassAttributePropertyFactory($attribute);
            if ($factory instanceof ContentClassAttributePropertyFactory) {
                $factories[$factory->providePropertyIdentifier()] = $factory;
            }
        }

        return $factories;
    }

    public function loadContentMetaPropertyFactory(string $identifier): ?ContentMetaPropertyFactory
    {
        $settings = [];
        if (eZINI::instance('ocopenapi.ini')->hasGroup('ContentMetaSettings')) {
            $settings = eZINI::instance('ocopenapi.ini')->group('ContentMetaSettings');
        }

        $customMetaPropertyFactory = 'null';
        $classIdentifier = $this->contentClass->attribute('identifier');
        if (isset($settings['ContentMetaPropertyFactories'][$classIdentifier . '/' . $identifier])) {
            $customMetaPropertyFactory = $settings['ContentMetaPropertyFactories'][$classIdentifier . '/' . $identifier];
        } elseif (isset($settings['ContentMetaPropertyFactories'][$identifier])) {
            $customMetaPropertyFactory = $settings['ContentMetaPropertyFactories'][$identifier];
        } elseif (isset($this->metaDefaults[$identifier])) {
            $customMetaPropertyFactory = $this->metaDefaults[$identifier];
        }
        if (class_exists($customMetaPropertyFactory)) {
            return new $customMetaPropertyFactory($this->contentClass, $identifier);
        } else {
            Logger::instance()->error(
                "ContentMetaPropertyFactory not found",
                ['identifier' => $identifier, 'method' => __METHOD__]
            );
            $fallbackFactory = new ContentMetaPropertyFactory($this->contentClass, $identifier);
            if ($fallbackFactory->isRequired()) {
                return $fallbackFactory;
            }
        }

        return null;
    }

    /**
     * @return NullSchemaFactory|ContentClassAttributePropertyFactory|null
     */
    public function loadContentClassAttributePropertyFactory(
        eZContentClassAttribute $attribute
    ) {
        $settings = [];
        if (eZINI::instance('ocopenapi.ini')->hasGroup('ClassAttributeSettings')) {
            $settings = eZINI::instance('ocopenapi.ini')->group('ClassAttributeSettings');
        }

        $customAttributePropertyFactory = 'null';

        $classIdentifier = $this->contentClass->attribute('identifier');
        $identifier = $attribute->attribute('identifier');
        $dataType = $attribute->attribute('data_type_string');

        if (isset($settings['ClassAttributePropertyFactories'][$classIdentifier . '/' . $identifier])) {
            $customAttributePropertyFactory = $settings['ClassAttributePropertyFactories'][$classIdentifier . '/' . $identifier];
        } elseif (isset($settings['ClassAttributePropertyFactories'][$identifier])) {
            $customAttributePropertyFactory = $settings['ClassAttributePropertyFactories'][$identifier];
        } elseif (isset($settings['ClassAttributePropertyFactories'][$dataType])) {
            $customAttributePropertyFactory = $settings['ClassAttributePropertyFactories'][$dataType];
        } elseif (isset($this->datatypeDefaults[$dataType])) {
            $customAttributePropertyFactory = $this->datatypeDefaults[$dataType];
        }

        if (class_exists($customAttributePropertyFactory)) {
            return new $customAttributePropertyFactory($this->contentClass, $attribute);
        } else {
            Logger::instance()->error("ContentClassAttributePropertyFactory not found", [
                'datatype' => $dataType,
                'class' => $classIdentifier,
                'attribute' => $identifier,
                'method' => __METHOD__,
            ]);
            $fallbackFactory = new ContentClassAttributePropertyFactory($this->contentClass, $attribute);
            if ($fallbackFactory->isRequired()) {
                return $fallbackFactory;
            }
        }

        return null;
    }
}