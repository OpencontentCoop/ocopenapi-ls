<?php

namespace Opencontent\OpenApi\OperationFactory;

use ezpRestMvcResult;
use Opencontent\OpenApi\EndpointFactory\ChildEndpointFactoryInterface;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\SchemaFactory\ClassAttributeSchemaFactoryInterface;
use Opencontent\OpenApi\SchemaFactory\ContentClassAttributePropertyFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaSerializer;
use Opencontent\Opendata\Api\ContentRepository;
use Opencontent\Opendata\Api\Exception\OutOfRangeException;

trait ChildOperationFactoryTrait
{
    protected $propertyFactory;

    protected $contentRepository;

    /**
     * @return ContentRepository
     * @throws OutOfRangeException
     */
    public function getContentRepository()
    {
        if ($this->contentRepository === null) {
            $this->contentRepository = new ContentRepository();
            $currentEnvironment = new \FullEnvironmentSettings();
            $this->contentRepository->setEnvironment($currentEnvironment);
            $currentEnvironment->__set('request', $this->getCurrentRequest());
        }

        return $this->contentRepository;
    }

    /**
     * @param ChildEndpointFactoryInterface $endpointFactory
     * @return ezpRestMvcResult
     * @throws Exception
     */
    protected function getParentOperationResult(ChildEndpointFactoryInterface $endpointFactory)
    {
        $parentEndpoint = $endpointFactory->getParentEndpointFactory();
        $parentOperation = $endpointFactory->getParentOperationFactory();

        return $parentOperation
            ->setCurrentRequest($this->getCurrentRequest())
            ->handleCurrentRequest($parentEndpoint);
    }

    /**
     * @return ContentClassAttributePropertyFactory
     */
    protected function getPropertyFactory()
    {
        if ($this->propertyFactory === null) {
            $schemaFactory = $this->getSchemaFactories()[0];

            if ($schemaFactory instanceof ClassAttributeSchemaFactoryInterface) {
                $this->propertyFactory = ContentClassSchemaSerializer::loadContentClassAttributePropertyFactory(
                    $schemaFactory->getClass(),
                    $schemaFactory->getClassAttribute()
                );
            } else {
                throw new \InvalidArgumentException(
                    sprintf("Schema factory must be an instance of %s, instance of %s given", ClassAttributeSchemaFactoryInterface::class, get_class($schemaFactory))
                );
            }
        }

        return $this->propertyFactory;
    }
}