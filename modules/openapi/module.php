<?php

$Module = [
    'name' => 'OpenApi HTML',
];

$ViewList = [];
$ViewList['doc'] = [
    'functions' => ['doc'],
    'script' => 'doc.php',
    'params' => [],
    'unordered_params' => [],
    "default_navigation_part" => 'ezsetupnavigationpart',
];
$ViewList['terms'] = [
    'functions' => ['doc'],
    'script' => 'terms.php',
    'params' => [],
    'unordered_params' => [],
    "default_navigation_part" => 'ezsetupnavigationpart',
];
$ViewList['inspect'] = [
    'functions' => ['inspect'],
    'script' => 'inspect.php',
    'params' => [],
    'unordered_params' => [],
    "default_navigation_part" => 'ezsetupnavigationpart',
];

$FunctionList = [];
$FunctionList['doc'] = [];
$FunctionList['inspect'] = [];