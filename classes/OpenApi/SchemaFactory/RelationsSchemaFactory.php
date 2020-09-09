<?php

namespace Opencontent\OpenApi\SchemaFactory;

use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\EndpointFactory\RelationsEndpointFactory;
use Opencontent\OpenApi\Loader;
use Opencontent\OpenApi\OperationFactory\Relations\ReadOperationFactory;
use Opencontent\OpenApi\SchemaFactory;

class RelationsSchemaFactory extends SchemaFactory implements ClassAttributeSchemaFactoryInterface
{
    protected $classAttributeId;

    protected $classAttribute;

    protected $class;

    private $classConstraintList;

    private static $resourceEndpointPaths = [];

    public function __construct($classAttributeId)
    {
        $this->classAttributeId = $classAttributeId;
        $this->classConstraintList = \eZContentClassAttribute::fetch($this->classAttributeId)->content()['class_constraint_list'];
        $this->name = $this->toCamelCase('related_resource');

    }

    /**
     * @return integer
     */
    public function getClassAttributeId()
    {
        return $this->classAttributeId;
    }

    /**
     * @return \eZContentClassAttribute
     */
    public function getClassAttribute()
    {
        if ($this->classAttribute === null){
            $this->classAttribute = \eZContentClassAttribute::fetch($this->getClassAttributeId());
        }
        return $this->classAttribute;
    }

    /**
     * @return \eZContentClass
     */
    public function getClass()
    {
        if ($this->class === null){
            $this->class = \eZContentClass::fetch($this->getClassAttribute()->attribute('contentclass_id'));
        }
        return $this->class;
    }

    /**
     * @return OA\Schema
     */
    public function generateSchema()
    {
        $uriDescription = 'Resource uri';
        if (RelationsSchemaFactory::getResourceEndpointPath($this->getClassAttributeId()) != '/'){
            $uriDescription .= ' from ' . RelationsSchemaFactory::getResourceEndpointPath($this->getClassAttributeId());
        }

        $schema = new OA\Schema();
        $schema->type = 'object';
        $schema->properties = [
            'id' => $this->generateSchemaProperty(['type' => 'string', 'description' => 'Resource Id', 'readOnly' => true]),
            'uri' => $this->generateSchemaProperty(['type' => 'string', 'description' => $uriDescription]),
            'priority' => $this->generateSchemaProperty(['type' => 'integer', 'description' => 'Priority']),
        ];

        return $schema;
    }

    /**
     * @return OA\RequestBody
     */
    public function generateRequestBody()
    {
        $schema = $this->generateSchema();
        $schema->title = $schema->title . 'Struct';
        unset($schema->properties['id']);

        return new OA\RequestBody(['application/json' => new OA\MediaType([
            'schema' => $schema
        ])]);
    }

    public function serialize()
    {
        return serialize([
            'classAttributeId' => $this->classAttributeId,
            'name' => $this->name,
        ]);
    }

    /**
     * @param $classAttributeId
     * @param bool $withUrl
     * @return string
     */
    public static function getResourceEndpointPath($classAttributeId, $withUrl = true)
    {
        if (!isset(self::$resourceEndpointPaths[$classAttributeId])){
            self::$resourceEndpointPaths[$classAttributeId] = '/';
            $endpoint = Loader::instance()->getEndpointProvider()->getEndpointFactoryCollection()->findOneByCallback(function ($endpoint) use($classAttributeId) {
                if ($endpoint instanceof RelationsEndpointFactory) {
                    if ($endpoint->getOperationByMethod('get') instanceof ReadOperationFactory
                        && $endpoint->getClassAttributeId() == $classAttributeId) {
                        return true;
                    }
                }
                return false;
            });
            if ($endpoint instanceof RelationsEndpointFactory) {
                $resourceEndpointPath = $endpoint->hasRelatedEndpoint() ? $endpoint->getRelatedEndpoint()->getPath() : '';
                $resourceEndpointPathParts = explode('/', $resourceEndpointPath);
                array_pop($resourceEndpointPathParts);
                self::$resourceEndpointPaths[$classAttributeId] = implode('/', $resourceEndpointPathParts) . '/';
            }
        }

        $baseUrl = $withUrl && self::$resourceEndpointPaths[$classAttributeId] != '/' ? Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl : '';

        return $baseUrl . self::$resourceEndpointPaths[$classAttributeId];
    }
}