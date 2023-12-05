<?php

/** @var eZModule $module */
$module = $Params['Module'];
$section = $Params['Section'];

$module->redirectTo('openapi/doc/' . $section);