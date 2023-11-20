<?php


namespace Opencontent\OpenApi\SchemaBuilder;

class Settings
{
    public $contactEmail = 'support@opencontent.it';

    public $termsOfServiceUrl;

    public $apiTitle;

    public $apiDescription;

    public $apiVersion;

    public $apiId;

    public $endpointUrl;

    public $cacheEnabled;

    public $debugEnabled;

    public $rateLimitEnabled;

    public $rateLimitDocumentationEnabled;

    public $documentationSections = [];

    public function hasDocumentationSection($name): bool
    {
        return isset($this->documentationSections[$name]);
    }

    public function getDocumentationSection($name): array
    {
        return $this->documentationSections[$name] ?? [];
    }
}
