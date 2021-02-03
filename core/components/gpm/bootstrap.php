<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */
require_once $namespace['path'] . 'vendor/autoload.php';

$modx->addPackage('GPM\Model', $namespace['path'] . 'src/', null, 'GPM\\');

$modx->services->add('\\GPM\\Logger\\MODX', function() use ($modx) {
    return new \GPM\Logger\MODX($modx);
});

$modx->services->add('\\GPM\\Operations\\ParseSchema', function($c) use ($modx) {
    return new \GPM\Operations\ParseSchema($modx, $c->get('\\GPM\\Logger\\MODX'));
});

$modx->services->add('\\GPM\\Operations\\Build', function($c) use ($modx) {
    return new \GPM\Operations\Build($modx, $c->get('\\GPM\\Logger\\MODX'));
});

$modx->services->add('\\GPM\\Operations\\Install', function($c) use ($modx) {
    return new \GPM\Operations\Install($modx, $c->get('\\GPM\\Operations\\ParseSchema'), $c->get('\\GPM\\Logger\\MODX'));
});

$modx->services->add('\\GPM\\Operations\\Update', function($c) use ($modx) {
    return new \GPM\Operations\Update($modx, $c->get('\\GPM\\Operations\\ParseSchema'), $c->get('\\GPM\\Logger\\MODX'));
});

$modx->services->add('\\GPM\\Operations\\Remove', function($c) use ($modx) {
    return new \GPM\Operations\Remove($modx, $c->get('\\GPM\\Logger\\MODX'));
});

$modx->services->add('\\GPM\\Operations\\Create', function($c) use ($modx) {
    return new \GPM\Operations\Create($modx, $c->get('\\GPM\\Logger\\MODX'));
});

$modx->services->add('gpm', function($c) use ($modx) {
    return new GPM\GPM($modx);
});
