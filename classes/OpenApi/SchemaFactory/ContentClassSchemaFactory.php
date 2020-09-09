<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\SchemaFactory;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;

class ContentClassSchemaFactory extends SchemaFactory
{
    protected $classIdentifier;

    protected $serializer;

    public function __construct($classIdentifier)
    {
        $this->classIdentifier = $classIdentifier;
        $this->name = $this->toCamelCase($classIdentifier);
    }

    protected function getSerializer()
    {
        if ($this->serializer === null){
            $this->serializer = new ContentClassSchemaSerializer();
        }

        return $this->serializer;
    }

    /**
     * @param mixed $serializer
     * @return ContentClassSchemaFactory
     */
    public function setSerializer($serializer)
    {
        $this->serializer = $serializer;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getClassIdentifier()
    {
        return $this->classIdentifier;
    }

    /**
     * @return OA\Schema
     */
    public function generateSchema()
    {
        return $this->getSerializer()->generateSchema($this->classIdentifier, $this->name);
    }

    /**
     * @return OA\RequestBody
     */
    public function generateRequestBody()
    {
        return new OA\RequestBody(['application/json' => new OA\MediaType([
            'schema' => new OA\Reference('#/components/schemas/' . $this->name)
        ])]);
    }

    public function serialize()
    {
        return serialize([
            'classIdentifier' => $this->classIdentifier,
            'name' => $this->name,
        ]);
    }

    /**
     * @param Content $value
     * @param string $locale
     * @return array
     */
    public function serializeValue($value, $locale)
    {
        return $this->getSerializer()->serializeValue($this->classIdentifier, $this->name, $value, $locale);
    }

    /**
     * @param PayloadBuilder $payloadBuilder
     * @param array $payload
     * @param string $locale
     */
    public function serializePayload($payloadBuilder, $payload, $locale)
    {
        $this->getSerializer()->serializePayload($this->classIdentifier, $this->name, $payloadBuilder, $payload, $locale);
    }
}