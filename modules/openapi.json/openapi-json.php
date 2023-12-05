<?php

use Opencontent\OpenApi;

try {
    header('HTTP/1.1 200 OK');
    $builder = OpenApi\Loader::instance()->getSchemaBuilder(eZHTTPTool::instance()->attribute('get'));
    $data = $builder->build()->toArray();
    //$data = $builder->getEndpoints();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $data = ['error' => $e->getMessage()];
}

if (isset($_GET['debug'])) {
    //echo '<pre>';print_r($data);echo '</pre>';
    eZDisplayDebug();
}else{
    $filename = 'openapi.json';
    if (eZHTTPTool::instance()->hasGetVariable('section')){
        $filename = eZCharTransform::instance()->transformByGroup(eZHTTPTool::instance()->getVariable('section'), 'identifier') . '.json';
    }
    header('Content-Type: application/json');
    header("Content-Disposition: attachment; filename=$filename");
    echo json_encode($data);
}

eZExecution::cleanExit();