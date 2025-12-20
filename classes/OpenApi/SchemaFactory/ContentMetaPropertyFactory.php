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

    /**
     * @var array
     */
    private $properties;

    /**
     * @var false
     */
    private $required;

    public function __construct(
        \eZContentClass $class,
        string $metaIdentifier,
        array $properties = null,
        bool $required = false
    ) {
        $this->class = $class;
        $this->metaIdentifier = $metaIdentifier;
        $this->properties = $properties ?? [
            "type" => 'string',
            "description" => $this->metaIdentifier
        ];
        $this->required = $required;
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
        return $this->properties;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function serializeValue(Content $content, $locale)
    {
        return $content->metadata->{$this->metaIdentifier};
    }

    public function serializePayload(PayloadBuilder $payloadBuilder, array $payload, $locale)
    {

    }
}