<?php

namespace Opencontent\OpenApi\SchemaFactory;

use Opencontent\Opendata\Api\Values\Content;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;

class ContentMetaPropertyFactory
{
    protected $class;

    protected $metaIdentifier;

    public function __construct(\eZContentClass $class, $metaIdentifier)
    {
        $this->class = $class;
        $this->metaIdentifier = $metaIdentifier;
    }

    public function providePropertyIdentifier()
    {
        return $this->metaIdentifier;
    }

    /**
     * @return array
     */
    public function provideProperties()
    {
        return [
            "type" => "string",
            "description" => $this->metaIdentifier
        ];
    }

    public function isRequired()
    {
        return false;
    }

    public function serializeValue(Content $content, $locale)
    {
        return $content->metadata->{$this->metaIdentifier};
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {

    }
}