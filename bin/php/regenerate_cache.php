<?php

use Opencontent\OpenApi\Loader;

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "OpenContent OpenApi cache regenerate" ),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions();
$script->initialize();
$script->setUseDebugAccumulators( true );

$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

try {

    $loader = Loader::instance();
    if ($loader->isCacheEnabled()) {

        $cli->output('Clear cache');
        Loader::clearCache();

        $cli->output('Generate endpoint');
        $loader->getEndpointProvider()->getEndpointFactoryCollection();

        $cli->output('Generate schema');
        $loader->getSchemaBuilder()->build();
    }

}catch (Exception $e){
    $cli->error($e->getMessage());
    print_r($e->getTraceAsString());
}


$script->shutdown();