<?php

namespace Opencontent\OpenApi\SchemaFactory;

use Opencontent\OpenApi\EndpointFactory\NodeClassesEndpointFactory;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;

class ContentMetaPropertyFactory
{
    protected $class;

    protected $metaIdentifier;

    /**
     * @var NodeClassesEndpointFactory
     */
    protected $contextEndpoint;

    public function __construct(\eZContentClass $class, $metaIdentifier)
    {
        $this->class = $class;
        $this->metaIdentifier = $metaIdentifier;
    }

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