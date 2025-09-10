<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\NotYetImplementedException;
use Opencontent\OpenApi\OperationFactory\SchemaReferenceGeneratorTrait;
use Opencontent\OpenApi\SchemaBuilder\Operation;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;

abstract class OperationFactory implements \JsonSerializable, \Serializable
{
    use SchemaBuilderToolsTrait;
    use SchemaReferenceGeneratorTrait;

    protected $id;

    protected $name;

    protected $method;

    /**
     * @var string[]
     */
    protected $tags;

    /**
     * @var string
     */
    protected $summary;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var SchemaFactory[]
     */
    protected $schemaFactories;

    /**
     * @var \ezpRestRequest
     */
    protected $currentRequest;

    /**
     * @param SchemaFactory[] $schemas
     */
    public function __construct(array $schemas = [])
    {
        $this->schemaFactories = $schemas;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $name
     * @return OperationFactory
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * @return SchemaFactory[]
     */
    public function getSchemaFactories()
    {
        return $this->schemaFactories;
    }

    /**
     * @param SchemaFactory[] $schemas
     * @return OperationFactory
     */
    public function setSchemaFactories($schemas)
    {
        $this->schemaFactories = $schemas;
        return $this;
    }

    /**
     * @param SchemaFactory[] $schemas
     * @return OperationFactory
     */
    public function appendSchemaFactories($schemas)
    {
        foreach ($schemas as $schema) {
            if (!$this->hasSchemaFactories($schema->getName())) {
                $this->schemaFactories[] = $schema;
            }
        }
        return $this;
    }

    protected function hasSchemaFactories($schemaName)
    {
        foreach ($this->schemaFactories as $schema) {
            if ($schema->getName() == $schemaName) {
                return true;
            }
        }

        return false;
    }

    public function __toString()
    {
        $schemaNames = trim(array_reduce($this->schemaFactories, function ($carry, $item) {
            $carry .= (string)$item . ' ';
            return $carry;
        }));

        return ($this->name ? $this->name : (string)$this->method) . "({$schemaNames})";
    }

    public function jsonSerialize()
    {
        return get_object_vars($this);
    }

    public function getResponses()
    {
        return [
            '404' => 'Not found',
        ];
    }

    /**
     * @return OA\Operation
     */
    public function generateOperation()
    {
        $operationDefinition = new Operation(
            $this->generateResponseList(),
            (string)$this->getId(),
            (string)$this->getSummary(),
            $this->generateOperationAdditionalProperties()
        );

        $operationDefinition->responses['401'] = new OA\Response('Unauthorized', null, $this->generateResponseHeaders());
        if (\OpenApiRateLimit::instance()->isEnableDocumentation()) {
            $operationDefinition->responses['429'] = new OA\Response('Too Many Requests', null, $this->generateResponseHeaders());
        }

        ksort($operationDefinition->responses);

        return $operationDefinition;
    }

    /**
     * @return OA\Response[]
     */
    protected function generateResponseList()
    {
        return [
            '404' => new OA\Response('Not found', null, $this->generateResponseHeaders(true)),
        ];
    }

    protected function generateResponseHeaders($isError = false)
    {
        $headers = [];
        if (\OpenApiRateLimit::instance()->isEnableDocumentation()) {
            $headers = [
                'X-RateLimit-Limit' => new OA\Header(
                    'The maximum number of requests that the client is allowed to make in this window.',
                    ['schema' => new OA\Schema(['type' => 'integer', 'format' => 'int32',])]
                ),
                'X-RateLimit-Remaining' => new OA\Header(
                    'The number of requests allowed in the current window.',
                    ['schema' => new OA\Schema(['type' => 'integer', 'format' => 'int32',])]
                ),
                'X-RateLimit-Reset' => new OA\Header(
                    'The relative time in seconds when the rate limit window will be reset.',
                    ['schema' => new OA\Schema(['type' => 'integer', 'format' => 'int32',])]
                ),
            ];
        }
        if ($isError){
            $headers['X-Api-Error-Type'] = new OA\Header(
                'The error identifier.',
                ['schema' => new OA\Schema(['type' => 'string'])]
            );
            $headers['X-Api-Error-Message'] = new OA\Header(
                'The error message.',
                ['schema' => new OA\Schema(['type' => 'string'])]
            );
        }

        return count($headers) ? $headers : null;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return OperationFactory
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     * @return OperationFactory
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return OperationFactory
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param string[] $tags
     * @return OperationFactory
     */
    public function setTags($tags)
    {
        $this->tags = $tags;
        return $this;
    }

    protected function generateOperationAdditionalProperties()
    {
        return [
            'summary' => (string)$this->getDescription(),
            'tags' => $this->getTags(),
        ];
    }

    public function serialize()
    {
        return serialize([
            'id' => $this->getId(),
            'description' => $this->description,
            'name' => $this->name,
            'method' => $this->method,
            'summary' => $this->summary,
            'tags' => $this->tags,
            'schemaFactories' => $this->schemaFactories,
        ]);
    }

    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        foreach ($data as $key => $value){
            $this->{$key} = $value;
        }
    }

    /**
     * @return \ezpRestRequest
     */
    public function getCurrentRequest()
    {
        return $this->currentRequest;
    }

    /**
     * @param \ezpRestRequest $currentRequest
     * @return OperationFactory
     */
    public function setCurrentRequest($currentRequest)
    {
        $this->currentRequest = $currentRequest;
        return $this;
    }

    /**
     * @return mixed
     * @throws InvalidPayloadException
     */
    public function getCurrentPayload()
    {
        $input = $this->getCurrentRequest()->body;
        $data = json_decode($input, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new InvalidPayloadException("Invalid json", 1);
        }
        return $data;
    }

    /**
     * @param EndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        throw new NotYetImplementedException($this->getMethod(), $endpointFactory->getPath(), static::class);
    }

    protected function getCurrentRequestParameter($name)
    {
        if ($this->currentRequest instanceof \ezpRestRequest) {
            $parameters = array_merge(
                $this->currentRequest->variables,
                $this->currentRequest->get
            );
            return isset($parameters[$name]) ? $parameters[$name] : null;
        }

        return null;
    }

    public function getCurrentRequestLanguage()
    {
        if ($this->currentRequest instanceof \ezpRestRequest && !empty($this->currentRequest->accept->languages)) {
            $languageCode = array_shift($this->currentRequest->accept->languages);
            array_unshift($this->currentRequest->accept->languages, $languageCode);
            foreach (SchemaBuilderToolsTrait::getLanguageList() as $language => $code){
                if ($code == $languageCode){
                    return $language;
                }
            }
        }

        return \eZContentObject::defaultLanguage();
    }
}