<?php

namespace Opencontent\OpenApi\OperationFactory\ContentObject;

use OpenApiEnvironmentSettings;
use Opencontent\OpenApi\EndpointFactory;
use Opencontent\OpenApi\Exception;
use Opencontent\OpenApi\Exceptions\InvalidParameterException;
use Opencontent\OpenApi\Exceptions\InvalidPayloadException;
use Opencontent\OpenApi\Exceptions\NotFoundException;
use Opencontent\OpenApi\Exceptions\UpdateContentException as OpenApiUpdateContentException;
use Opencontent\OpenApi\OperationFactory;
use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;
use Opencontent\Opendata\Api\Exception\CreateContentException;
use Opencontent\Opendata\Api\Exception\UpdateContentException;

class MergePatchOperationFactory extends OperationFactory\MergePatchOperationFactory
{
    use ContentRepositoryTrait;

    public function getSummary()
    {
        $resourceNames = [];
        foreach ($this->schemaFactories as $schemaFactory) {
            $resourceNames[] = $schemaFactory->getName();
        }
        $or = \ezpI18n::tr('ocopenapi', ' or ');
        $resourceName = implode($or, $resourceNames);
        return \ezpI18n::tr('ocopenapi', 'Update an existing %name resource with properties to be changed', null, ['%name' => $resourceName]);
    }

    /**
     * @param EndpointFactory\NodeClassesEndpointFactory $endpointFactory
     * @return \ezpRestMvcResult
     * @throws Exception
     */
    public function handleCurrentRequest(EndpointFactory $endpointFactory)
    {
        $result = new \ezpRestMvcResult();
        $payload = $this->getCurrentPayload();
        $requestId = $this->getCurrentRequestParameter($this->getItemIdLabel());
        if (empty($requestId)) {
            throw new InvalidParameterException($this->getItemIdLabel(), $requestId);
        }

        $this->getResource($endpointFactory, $requestId, $this->getCurrentRequestLanguage());
        $object = \eZContentObject::fetchByRemoteID($requestId);
        if (!$object instanceof \eZContentObject) {
            throw new NotFoundException($requestId);
        }
        if (isset($payload['_id'])) {
            throw new InvalidPayloadException('Invalid field _id');
        }
        $payload['_id'] = (int)$object->attribute('id');

        try {
            /** @var ContentClassSchemaFactory[] $schemaFactories */
            $schemaFactories = $this->getSchemaFactories();
            if (count($schemaFactories) > 1) {
                foreach ($schemaFactories as $schemaFactory) {
                    if ($schemaFactory->getClassIdentifier() == $object->attribute('class_identifier')) {
                        $payload[OpenApiEnvironmentSettings::DISCRIMINATOR_PROPERTY_NAME] = OpenApiEnvironmentSettings::DISCRIMINATED_SCHEMA_PREFIX . $schemaFactory->getName();
                    }
                }
            }

            /** @var OpenApiEnvironmentSettings $env */
            $env = $this->getContentRepository($endpointFactory)->getCurrentEnvironmentSettings();
            $env->setIsPatch();

            $response = $this->getContentRepository($endpointFactory)->update($payload);
            $result->variables = $response['content'];

        } catch (UpdateContentException $e) {
            throw new OpenApiUpdateContentException($e->getMessage(), $e->getCode(), $e);
        } catch (CreateContentException $e) {
            throw new OpenApiUpdateContentException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }
}
