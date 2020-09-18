<?php

/** @var eZModule $module */
$module = $Params['Module'];

$tpl = eZTemplate::factory();
$contentInfoArray = [
    'node_id' => null,
    'class_identifier' => null
];
$contentInfoArray['persistent_variable'] = [
    'show_path' => false,
];
if (is_array($tpl->variable('persistent_variable'))) {
    $contentInfoArray['persistent_variable'] = array_merge($contentInfoArray['persistent_variable'], $tpl->variable('persistent_variable'));
}
$Result['title_path'] = $Result['path'] = [[
    'text' => 'Api Terms',
    'url' => false,
    'url_alias' => false
]];
$Result['content_info'] = $contentInfoArray;
$Result['content'] = $tpl->fetch('design:openapi_terms.tpl');