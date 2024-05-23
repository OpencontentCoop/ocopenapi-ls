<?php

use Opencontent\OpenApi\EndpointDiscover\ChainEndpointFactoryDiscover;
use Opencontent\OpenApi\Loader;

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance([
    'description' => ("OpenContent OpenApi cache regenerate"),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true,
]);

$script->startup();

$options = $script->getOptions();
$script->initialize();
$script->setUseDebugAccumulators(true);

$_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

function printProviders($endpointProvider, $prefix = '')
{
    global $cli;
    if ($endpointProvider instanceof ChainEndpointFactoryDiscover) {
        foreach ($endpointProvider->getProviders() as $provider) {
            $cli->output($prefix . ' - ' . get_class($provider));
            printProviders($provider, '  ');
        }
    } else {
        $endpoints = $endpointProvider->getEndpointFactoryCollection();
        foreach ($endpoints as $endpoint) {
            \eZCLI::instance()->warning($prefix .  '   -> ' . $endpoint->getPath());
        }
    }
}

try {
    $loader = Loader::instance();
    if ($loader->isCacheEnabled()) {
        $cli->output('Clear cache');
        Loader::clearCache();

        $endpointProvider = $loader->getEndpointProvider();
        $cli->output('Generate endpoint from ' . get_class($endpointProvider));
        printProviders($endpointProvider);

        $cli->output('Generate schema');
        $loader->getSchemaBuilder()->build();
    }
} catch (Exception $e) {
    $cli->error($e->getMessage());
    print_r($e->getTraceAsString());
}


$script->shutdown();