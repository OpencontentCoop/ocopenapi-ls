<?php

namespace Opencontent\OpenApi;

use erasys\OpenApi as OpenApiBase;
use erasys\OpenApi\Spec\v3 as OA;
use Opencontent\OpenApi\OperationFactory\ContentObject\CreateOperationFactory;
use Opencontent\OpenApi\OperationFactory\ContentObject\UpdateOperationFactory;
use Opencontent\OpenApi\SchemaBuilder\InfoWithAdditionalProperties;
use Opencontent\OpenApi\SchemaBuilder\SchemaBuilderToolsTrait;
use Opencontent\OpenApi\SchemaBuilder\Settings;
use Opencontent\OpenApi\SchemaBuilder\SettingsProviderInterface;

class SchemaBuilder extends EndpointFactoryProvider implements SchemaBuilderInterface
{
    use SchemaBuilderToolsTrait;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @var EndpointFactoryProviderInterface
     */
    private $endpointProvider;

    /**
     * @var EndpointFactoryCollection
     */
    private $endpoints;

    public function __construct(SettingsProviderInterface $settingsProvider, EndpointFactoryProviderInterface $endpointProvider)
    {
        $this->settings = $settingsProvider->provideSettings();
        $this->endpointProvider = $endpointProvider;
    }

    /**
     * @return Settings
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param Settings $settings
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
    }

    public function getOperationFactoryById($operationId)
    {
        return $this->endpointProvider->getOperationFactoryById($operationId);
    }

    /**
     * @return OA\Document
     */
    public function build()
    {
        return new OA\Document(
            $this->buildInfo(),
            $this->buildPaths(),
            '3.0.1',
            [
                'servers' => $this->buildServers(),
                'tags' => $this->buildTags(),
                'components' => $this->buildComponents(),
                'security' => [['basicAuth' => []]]
            ]
        );
    }

    /**
     * @see https://opensource.zalando.com/restful-api-guidelines/#218
     * @return OA\Info
     */
    private function buildInfo()
    {
        $contact = new OA\Contact();
        $contact->email = $this->settings->contactEmail;

        return new InfoWithAdditionalProperties(
            (string)$this->settings->apiTitle,
            //@see https://opensource.zalando.com/restful-api-guidelines/#218
            (string)$this->settings->apiVersion,
            (string)$this->settings->apiDescription,
            [
                'termsOfService' => (string)$this->settings->termsOfServiceUrl,
                'contact' => $contact,
                'license' => new OA\License("GNU General Public License, version 2", "https://www.gnu.org/licenses/old-licenses/gpl-2.0.html"),
                //@see https://opensource.zalando.com/restful-api-guidelines/#215
                'xApiId' => new OpenApiBase\ExtensionProperty('api-id', (string)$this->settings->apiId),
                //@see https://opensource.zalando.com/restful-api-guidelines/#219
                'xAudience' => new OpenApiBase\ExtensionProperty('audience', 'external-public'),
            ]
        );
    }

    /**
     * @return OA\PathItem[]
     */
    private function buildPaths()
    {
        $paths = [];

        foreach ($this->getEndpointFactoryCollection() as $endpoint) {
            if (!isset($paths[$endpoint->getPath()])) {
                $paths[$endpoint->getPath()] = $endpoint->generatePathItem();
            }
        }

        return $paths;
    }

    /**
     * @return EndpointFactoryCollection
     */
    public function getEndpointFactoryCollection()
    {
        if ($this->endpoints === null) {
            $this->endpoints = $this->endpointProvider->getEndpointFactoryCollection();
        }
        return $this->endpoints;
    }

    /**
     * @return OA\Server[]
     */
    private function buildServers()
    {
        return [
            new OA\Server((string)$this->settings->endpointUrl, 'Production server'),
        ];
    }

    /**
     * @return OA\Tag[]
     */
    private function buildTags()
    {
        $tags = [];

        foreach ($this->getEndpointFactoryCollection() as $endpoint) {
            $tags = array_unique(array_merge($tags, $endpoint->getOperationFactoryCollection()->getTags()));
        }

        $oaTags = [];
        foreach ($tags as $tag) {
            $oaTags[] = new OA\Tag($tag);
        }

        return $oaTags;
    }

    /**
     * @return OA\Components
     */
    private function buildComponents()
    {
        $components = new OA\Components();

        //@todo @see https://opensource.zalando.com/restful-api-guidelines/#104
        $components->securitySchemes = [
            'basicAuth' => new OA\SecurityScheme('http', null, ['scheme' => 'basic']),
        ];

        $schemas = [];
        foreach ($this->getEndpointFactoryCollection() as $endpoint) {
            foreach ($endpoint->getOperationFactoryCollection()->getSchemaFactories() as $schema) {
                if (!isset($schemas[$schema->getName()])) {
                    $schemas[$schema->getName()] = $schema->generateSchema();
                }
            }

        }

        $requestBodies = [];
        foreach ($this->getEndpointFactoryCollection() as $endpoint) {
            foreach ($endpoint->getOperationFactoryCollection()->getOperationFactories() as $operationFactory) {

                if (($operationFactory instanceof CreateOperationFactory || $operationFactory instanceof UpdateOperationFactory)
                    && count($operationFactory->getSchemaFactories()) > 1
                ) {
                    if (!isset($schemas[\OpenApiEnvironmentSettings::DISCRIMINATOR_SCHEMA_NAME])) {
                        $schemas[\OpenApiEnvironmentSettings::DISCRIMINATOR_SCHEMA_NAME] = new OA\Schema([
                            "type" => "object",
                            'properties' => [
                                \OpenApiEnvironmentSettings::DISCRIMINATOR_PROPERTY_NAME => $this->generateSchemaProperty([
                                    'type' => 'string',
                                    'title' => 'Content type (discriminator)'
                                ])
                            ],
                            'required' => ['content_type']
                        ]);
                    }

                    foreach ($operationFactory->getSchemaFactories() as $schema) {
                        $schemas[\OpenApiEnvironmentSettings::DISCRIMINATED_SCHEMA_PREFIX . $schema->getName()] = new OA\Schema([
                            'allOf' => [
                                new OA\Reference('#/components/schemas/' . \OpenApiEnvironmentSettings::DISCRIMINATOR_SCHEMA_NAME),
                                $schema->generateSchema()
                            ]
                        ]);
                    }
                } else {
                    foreach ($operationFactory->getSchemaFactories() as $schema) {
                        $requestBodies[$schema->getName()] = $schema->generateRequestBody();
                    }
                }
            }
        }

        ksort($schemas);
        $components->schemas = $schemas;

        ksort($requestBodies);
        $components->requestBodies = $requestBodies;

        return $components;
    }
}