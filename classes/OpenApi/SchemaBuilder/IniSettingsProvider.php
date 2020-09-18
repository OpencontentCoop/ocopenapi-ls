<?php

namespace Opencontent\OpenApi\SchemaBuilder;

class IniSettingsProvider implements SettingsProviderInterface
{
    public function provideSettings()
    {
        $settings = new Settings();

        $termsUrl = '/openapi/terms';
        \eZURI::transformURI($siteUrl, true, 'full');
        $settings->termsOfServiceUrl = $termsUrl;

        $endpointUrl = '/api/openapi';
        \eZURI::transformURI($endpointUrl, true, 'full');
        $settings->endpointUrl = $endpointUrl;

        $settings->apiTitle = \eZINI::instance()->variable('SiteSettings', 'SiteName') . ' Api';
        $settings->apiDescription = 'Web service to create and manage contents';
        $settings->apiId = \eZSolr::installationID();

        $settings->debugEnabled = \eZINI::instance()->variable('DebugSettings', 'DebugOutput') == 'enabled';

        $settings->cacheEnabled = true;

        return $settings;
    }

}