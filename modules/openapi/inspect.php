<?php

use Opencontent\OpenApi;

try {
    header('HTTP/1.1 200 OK');
    $data = OpenApi\Loader::instance()->getEndpointProvider()->getEndpointFactoryCollection();;
    //$data = $builder->getEndpoints();
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    $data = ['error' => $e->getMessage()];
}

if (isset($_GET['debug'])) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    eZDisplayDebug();
}else{
    header('Content-Type: application/json');
    echo json_encode($data);
}


eZExecution::cleanExit();