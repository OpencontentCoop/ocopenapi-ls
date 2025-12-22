<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory\NodeClassesEndpointFactory;
use Opencontent\OpenApi\SchemaFactory;
use Opencontent\Opendata\Api\Values\Content;
use Opencontent\OpenApi\OperationFactory\ContentObject\PayloadBuilder;
use BenMorel\OpenApiSchemaToJsonSchema\Convert;

class ContentClassSchemaFactory extends SchemaFactory
{
    protected $classIdentifier;

    protected $serializer;

    /**
     * @var NodeClassesEndpointFactory
     */
    protected $contextEndpoint;

    public function __construct($classIdentifier)
    {
        $this->classIdentifier = $classIdentifier;
        $this->name = $this->toCamelCase($classIdentifier);
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

    protected function getSerializer()
    {
        if ($this->serializer === null){
            $this->serializer = new ContentClassSchemaSerializer();
        }
        $this->serializer->setContextEndpoint($this->getContextEndpoint());

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

    public function generateJsonSchema(): \stdClass
    {
        $data = Convert::openapiSchemaToJsonSchema(
            $this->generateSchema()
        );
        $data->{'$schema'} = "http://json-schema.org/draft-04/schema#";

        return $data;
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

    /**
     * @param $data
     * @return array|true true if is valid else array of errors
     */
    public function validate($data)
    {
        if (class_exists('\JsonSchema\Validator')){
            $validator = new \JsonSchema\Validator;
            $data = json_decode(json_encode($data));
            $validator->validate(
                $data,
                $this->generateJsonSchema(),
                \JsonSchema\Constraints\Constraint::CHECK_MODE_TYPE_CAST
            );
            return $validator->isValid() ? true : $validator->getErrors();
        }

        return true;
    }
}