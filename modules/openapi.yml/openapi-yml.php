<?php

use Opencontent\OpenApi;

try {
    header('HTTP/1.1 200 OK');
    $builder = OpenApi\Loader::instance()->getSchemaBuilder();
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
    header('Content-Type: text/vnd.yaml');
    echo $data;
}

eZExecution::cleanExit();