<?php

use Opencontent\OpenApi\SchemaFactory\ContentClassSchemaFactory;

/** @var eZModule $Module */
$Module = $Params['Module'];
$Module->setExitStatus(eZModule::STATUS_IDLE);
$http = eZHTTPTool::instance();
$handler = $Params['Handler'];
$classIdentifier = strtolower($Params['Identifier']);

try {
    //@todo astrarre e gestire gli schema provider con un handler dedicato
    if (strtolower($handler) !== strtolower(RemoteIndexContentClassSchemaSerializer::SCHEMA_PROVIDER_NAME)) {
        throw new Exception('Schema type not found');
    }
    header('HTTP/1.1 200 OK');
    $schemaFactory = new ContentClassSchemaFactory($classIdentifier);
    $schemaFactory->setSerializer(new RemoteIndexContentClassSchemaSerializer());
    $data = $schemaFactory->generateJsonSchema();

} catch (Exception $e) {
    //@todo gestire il codice di stato in modo coerente all'errore
    header('HTTP/1.1 400 Bad Request');
    $data = ['error' => 'Invalid argument'];
    eZDebug::writeError($e->getMessage(), __METHOD__);
}

header('Content-Type: application/json');
echo json_encode($data);
eZExecution::cleanExit();