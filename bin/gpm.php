<?php

$configCore = dirname(dirname(__FILE__)) . '/config.core.php';
if (!file_exists($configCore)) {
    require_once dirname(__DIR__) . '/core/components/gpm/vendor/autoload.php';

    $application = new Symfony\Component\Console\Application();
    $application->add(new \GPM\CLI\GPMInstall());

    $application->run();
    return;
}

define('MODX_API_MODE', true);

@include($configCore);
if (!defined('MODX_CORE_PATH')) {
    define('MODX_CORE_PATH', dirname(__FILE__) . '/core/');
}

if (!@require_once(MODX_CORE_PATH . "vendor/autoload.php")) {
    exit();
}

$modx = new \MODX\Revolution\modX();
if (!is_object($modx) || !($modx instanceof \MODX\Revolution\modX)) {
    exit();
}

$modx->initialize('mgr');
$application = new Symfony\Component\Console\Application();

// Operations
$parseSchema = $modx->services->get(\GPM\Operations\ParseSchema::class);
$install = $modx->services->get(\GPM\Operations\Install::class);
$update = $modx->services->get(\GPM\Operations\Update::class);
$build = $modx->services->get(\GPM\Operations\Build::class);
$remove = $modx->services->get(\GPM\Operations\Remove::class);
$create = $modx->services->get(\GPM\Operations\Create::class);

$keyAdd = $modx->services->get(\GPM\Operations\Key\Add::class);
$keyList = $modx->services->get(\GPM\Operations\Key\ListKeys::class);
$keyRemove = $modx->services->get(\GPM\Operations\Key\Remove::class);

// Commands
$application->add(new \GPM\CLI\PackageInstall($install));
$application->add(new \GPM\CLI\PackageBuild($build));
$application->add(new \GPM\CLI\PackageCreate($create));

/** @var \GPM\Model\GitPackage[] $packages */
$packages = $modx->getIterator(\GPM\Model\GitPackage::class);
foreach ($packages as $package) {
    if ($package->name === 'gpm') {
        continue;
    }
    $application->add(new \GPM\CLI\Update("{$package->dir_name}:update", $package, $update));
    $application->add(new \GPM\CLI\ParseSchema("{$package->dir_name}:schema", $package, $parseSchema));
    $application->add(new \GPM\CLI\Build("{$package->dir_name}:build", $package, $build));
    $application->add(new \GPM\CLI\Remove("{$package->dir_name}:remove", $package, $remove));
    $application->add(new \GPM\CLI\KeyAdd("{$package->dir_name}:key:add", $package, $keyAdd));
    $application->add(new \GPM\CLI\KeyList("{$package->dir_name}:key:list", $package, $keyList));
    $application->add(new \GPM\CLI\KeyRemove("{$package->dir_name}:key:remove", $package, $keyRemove));
}

$application->run();
