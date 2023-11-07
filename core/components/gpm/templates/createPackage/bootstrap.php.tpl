<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

// Add your classes to modx's autoloader
\MODX\Revolution\modX::getLoader()->addPsr4('{{$namespace}}\\', $namespace['path'] . 'src/');

if (!$modx->services->has('{{$lowCaseName}}')) {
    // Register base class in the service container
    $modx->services->add('{{$lowCaseName}}', function($c) use ($modx) {
        return new \{{$namespace}}\{{$name}}($modx);
    });

    // Load packages model, uncomment if you have DB tables
    //$modx->addPackage('{{$namespace}}\Model', $namespace['path'] . 'src/', null, '{{$namespace}}\\');
}
