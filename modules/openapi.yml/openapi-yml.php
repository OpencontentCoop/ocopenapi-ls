<?php

use Opencontent\OpenApi;

try {
    header('HTTP/1.1 200 OK');
    $builder = OpenApi\Loader::instance()->getSchemaBuilder(eZHTTPTool::instance()->attribute('get'));
    $data = $builder->build()->toYaml(10);
    //$data = $builder->getEndpoints();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $data = $e->getMessage();
}

if (isset($_GET['debug'])) {
    //echo '<pre>';print_r($data);echo '</pre>';
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