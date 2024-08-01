<?php
/**
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */
require_once $namespace['path'] . 'vendor/autoload.php';

$modx->addPackage('GPM\Model', $namespace['path'] . 'src/', null, 'GPM\\');

$modx->services->add(\GPM\Logger\MODX::class, function() use ($modx) {
    return new \GPM\Logger\MODX($modx);
});

$modx->services->add(\GPM\Endpoint\Endpoint::class, function($c) use ($modx) {
    return new \GPM\Endpoint\Endpoint($modx);
});

// region: --- package
$modx->services->add(\GPM\Operations\ParseSchema::class, function($c) use ($modx) {
    return new \GPM\Operations\ParseSchema($modx, $c->get(\GPM\Logger\MODX::class));
});

$modx->services->add(\GPM\Operations\Build::class, function($c) use ($modx) {
    return new \GPM\Operations\Build($modx, $c->get(\GPM\Logger\MODX::class));
});

$modx->services->add(\GPM\Operations\Install::class, function($c) use ($modx) {
    return new \GPM\Operations\Install(
        $modx,
        $c->get(\GPM\Operations\ParseSchema::class),
        $c->get(\GPM\Operations\Scripts\Run::class),
        $c->get(\GPM\Logger\MODX::class)
    );
});

$modx->services->add(\GPM\Operations\Update::class, function($c) use ($modx) {
    return new \GPM\Operations\Update(
        $modx,
        $c->get(\GPM\Operations\ParseSchema::class),
        $c->get(\GPM\Operations\Migrations\Run::class),
        $c->get(\GPM\Operations\Scripts\Run::class),
        $c->get(\GPM\Logger\MODX::class)
    );
});

$modx->services->add(\GPM\Operations\Remove::class, function($c) use ($modx) {
    return new \GPM\Operations\Remove(
        $modx,
        $c->get(\GPM\Operations\Scripts\Run::class),
        $c->get(\GPM\Logger\MODX::class)
    );
});

$modx->services->add(\GPM\Operations\Create::class, function($c) use ($modx) {
    return new \GPM\Operations\Create($modx, $c->get(\GPM\Logger\MODX::class));
});
// endregion

// region: --- key
$modx->services->add(\GPM\Operations\Key\Add::class, function($c) use ($modx) {
    return new \GPM\Operations\Key\Add($modx, $c->get(\GPM\Logger\MODX::class));
});

$modx->services->add(\GPM\Operations\Key\ListKeys::class, function($c) use ($modx) {
    return new \GPM\Operations\Key\ListKeys($modx, $c->get(\GPM\Logger\MODX::class));
});

$modx->services->add(\GPM\Operations\Key\Remove::class, function($c) use ($modx) {
    return new \GPM\Operations\Key\Remove($modx, $c->get(\GPM\Logger\MODX::class));
});
// endregion

// region: --- gpm
$modx->services->add(\GPM\Operations\GPM\Update::class, function($c) use ($modx) {
    return new \GPM\Operations\GPM\Update($modx, $c->get(\GPM\Operations\ParseSchema::class), $c->get(\GPM\Logger\MODX::class));
});
// endregion

// region: --- fred
$modx->services->add(\GPM\Operations\Fred\Export::class, function($c) use ($modx) {
    return new \GPM\Operations\Fred\Export($modx, $c->get(\GPM\Logger\MODX::class));
});
// endregion

// region: --- migrations
$modx->services->add(\GPM\Operations\Migrations\Run::class, function($c) use ($modx) {
    return new \GPM\Operations\Migrations\Run($modx, $c->get(\GPM\Logger\MODX::class));
});

$modx->services->add(\GPM\Operations\Migrations\Create::class, function($c) use ($modx) {
    return new \GPM\Operations\Migrations\Create($modx, $c->get(\GPM\Logger\MODX::class));
});
// endregion

// region: --- scripts
$modx->services->add(\GPM\Operations\Scripts\Run::class, function($c) use ($modx) {
    return new \GPM\Operations\Scripts\Run($modx, $c->get(\GPM\Logger\MODX::class));
});

$modx->services->add(\GPM\Operations\Scripts\Create::class, function($c) use ($modx) {
    return new \GPM\Operations\Scripts\Create($modx, $c->get(\GPM\Logger\MODX::class));
});
// endregion

$modx->services->add('gpm', function($c) use ($modx) {
    return new GPM\GPM($modx);
});
