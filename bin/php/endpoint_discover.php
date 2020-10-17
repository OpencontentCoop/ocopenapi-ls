<?php

use Opencontent\OpenApi\EndpointDiscover\RoleEndpointFactoryDiscover;

require 'autoload.php';

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "OpenContent OpenApi endpoint discover" ),
    'use-session' => false,
    'use-modules' => true,
    'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions();
$script->initialize();
$script->setUseDebugAccumulators( true );

$discover = new RoleEndpointFactoryDiscover($options['verbose'] ? $cli : null);
$endpoints = $discover->getEndpointFactoryCollection();

$rows = $endpoints;

if (!empty($rows)) {
    $table = new ezcConsoleTable(new ezcConsoleOutput(), 300);
    $headers = array_keys($rows[0]->toArray());
    foreach ($headers as $cell) {
        $table[0][]->content = $cell;
    }
    foreach ($rows as $index => $row) {
        $index++;
        $row = $row->toArray();
        foreach ($row as $cell) {
            if (is_array($cell)){
                $value = array_reduce($cell, function ($carry, $item){
                    $carry .= (string)$item . ' ';
                    return $carry;
                });
            }else{
                $value = (string)$cell;
            }
            $table[$index][]->content = $value;
        }
    }
    $table->outputTable();
}

$cli->output();

$script->shutdown();