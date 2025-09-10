<?php

use Opencontent\OpenApi;
use Symfony\Component\Yaml\Yaml;

try {
    header('HTTP/1.1 200 OK');
    $builder = OpenApi\Loader::instance()->getSchemaBuilder(eZHTTPTool::instance()->attribute('get'));
    $data = $builder->build()->toYaml(100, 2, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
    //$data = $builder->getEndpoints();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $data = $e->getMessage();
    $data .= $e->getTraceAsString();
}

if (isset($_GET['debug'])) {
    echo '<pre>';print_r($data);echo '</pre>';
    eZDisplayDebug();
}else{
    $filename = 'openapi.yaml';
    if (eZHTTPTool::instance()->hasGetVariable('section')){
        $filename = eZCharTransform::instance()->transformByGroup(eZHTTPTool::instance()->getVariable('section'), 'identifier') . '.yaml';
    }
    header('Content-Type: text/vnd.yaml');
    header("Content-Disposition: attachment; filename=$filename");
    echo $data;
}

eZExecution::cleanExit();