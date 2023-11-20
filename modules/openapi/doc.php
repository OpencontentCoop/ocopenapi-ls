<?php

use Opencontent\OpenApi\Loader;

/** @var eZModule $module */
$module = $Params['Module'];
$section = $Params['Section'];

$tpl = eZTemplate::factory();
$settings = Loader::instance()->getSettingsProvider()->provideSettings();
$useSections = count($settings->documentationSections) > 0;
$tpl->setVariable('sections', $settings->documentationSections);
$currentSection = false;
if (!$useSections && !empty($section)){
    $module->redirectTo('openapi/doc');
    return;
}
if (!empty($section) && $settings->hasDocumentationSection($section)) {
    $currentSection = $settings->getDocumentationSection($section);
}

$tpl->setVariable('endpoint_url', $settings->endpointUrl . '/');
if ($currentSection) {
    $tpl->setVariable('show_section_index', false);
    $tpl->setVariable('section', $currentSection);
    $tpl->setVariable('endpoint_url', $settings->endpointUrl . '/?section=' . $section);
} else {
    $tpl->setVariable('show_section_index', $useSections);
}

if (isset($_GET['debug'])) {
    $tpl->setVariable('load_page', true);
    echo $tpl->fetch('design:openapi.tpl');
    eZDisplayDebug();
    eZExecution::cleanExit();
} else {
    $tpl->setVariable('load_page', false);
    $contentInfoArray = [
        'node_id' => null,
        'class_identifier' => null,
    ];
    $contentInfoArray['persistent_variable'] = [
        'show_path' => $currentSection !== false,
        'has_container' => true,
    ];
    if (is_array($tpl->variable('persistent_variable'))) {
        $contentInfoArray['persistent_variable'] = array_merge(
            $contentInfoArray['persistent_variable'],
            $tpl->variable('persistent_variable')
        );
    }
    $Result['title_path'] = $Result['path'] = [
        [
            'text' => 'Api Doc',
            'url' => false,
            'url_alias' => false,
        ],
    ];
    if ($currentSection) {
        $Result['path'] = [
            [
                'text' => 'Rest Api Documentation',
                'url' => '/openapi/doc',
                'url_alias' => '/openapi/doc',
            ],
            [
                'text' => $currentSection['title'],
                'url' => '/openapi/doc/' . $section,
                'url_alias' => false,
            ],
        ];
    }
    $Result['content_info'] = $contentInfoArray;
    $Result['content'] = $tpl->fetch('design:openapi.tpl');
}