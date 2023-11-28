<?php

namespace Opencontent\OpenApi\SchemaBuilder;

use eZINI;

class IniSettingsProvider implements SettingsProviderInterface
{
    private $settings;

    public function provideSettings()
    {
        if ($this->settings === null) {
            $settings = new Settings();

            $termsUrl = '/openapi/terms';
            \eZURI::transformURI($siteUrl, true, 'full');
            $settings->termsOfServiceUrl = $termsUrl;

            $endpointUrl = '/api/openapi';
            \eZURI::transformURI($endpointUrl, true, 'full');
            $settings->endpointUrl = $endpointUrl;

            $settings->apiTitle = eZINI::instance()->variable('SiteSettings', 'SiteName') . ' Rest Api';
            $settings->apiDescription = 'Web service to create and manage contents';
            $settings->apiId = \eZSolr::installationID();

            $settings->debugEnabled = eZINI::instance()->variable('DebugSettings', 'DebugOutput') == 'enabled';
            $settings->cacheEnabled = true;

            $ocopenapaIni = eZINI::instance('ocopenapi.ini');
            if ($ocopenapaIni->hasVariable('GeneralSettings', 'RateLimit')) {
                $settings->rateLimitEnabled = $ocopenapaIni->variable('GeneralSettings', 'RateLimit') == 'enabled';
            }
            if ($ocopenapaIni->hasVariable('GeneralSettings', 'RateLimitDocumentation')) {
                $settings->rateLimitDocumentationEnabled = $ocopenapaIni->variable('GeneralSettings', 'RateLimitDocumentation') == 'enabled';
            }

            if ($ocopenapaIni->hasVariable('Documentation', 'WithSections') && $ocopenapaIni->variable('Documentation', 'WithSections') == 'enabled') {
                $sections = (array)$ocopenapaIni->variable('Documentation', 'Sections');
                foreach ($sections as $section){
                    if ($ocopenapaIni->hasGroup('Section_' . $section)){
                        $settings->documentationSections[$section] = $ocopenapaIni->group('Section_' . $section);
                    }
                }
            }

            if ($ocopenapaIni->hasVariable('PDND', 'JwtAuthentication')) {
                $settings->jwtAccessEnabled = $ocopenapaIni->variable('PDND', 'PDNDJwtAuthentication') === 'enabled';
            }

            $this->settings = $settings;
        }

        return $this->settings;
    }

}
