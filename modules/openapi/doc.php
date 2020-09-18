<?php

use Opencontent\OpenApi\Loader;

$tpl = eZTemplate::factory();
$tpl->setVariable('endpoint_url', Loader::instance()->getSettingsProvider()->provideSettings()->endpointUrl . '/');
if (isset($_GET['debug'])) {
    $tpl->setVariable('load_page', true);
    echo $tpl->fetch('design:openapi.tpl');
    eZDisplayDebug();
    eZExecution::cleanExit();
} else {
    $tpl->setVariable('load_page', false);
    $contentInfoArray = [
        'node_id' => null,
        'class_identifier' => null
    ];
    $contentInfoArray['persistent_variable'] = [
        'show_path' => false,
        'has_container' => true,
    ];
    if (is_array($tpl->variable('persistent_variable'))) {
        $contentInfoArray['persistent_variable'] = array_merge($contentInfoArray['persistent_variable'], $tpl->variable('persistent_variable'));
    }
    $Result['title_path'] = $Result['path'] = [[
        'text' => 'Api Doc',
        'url' => false,
        'url_alias' => false
    ]];
    $Result['content_info'] = $contentInfoArray;
    $Result['content'] = $tpl->fetch('design:openapi.tpl');
}