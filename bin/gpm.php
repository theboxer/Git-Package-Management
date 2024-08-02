<?php

use GPM\Config\Config;
use GPM\Logger\Stealth;

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

$migrationsRun = $modx->services->get(\GPM\Operations\Migrations\Run::class);
$migrationsCreate = $modx->services->get(\GPM\Operations\Migrations\Create::class);

$scriptRun = $modx->services->get(\GPM\Operations\Scripts\Run::class);
$scriptCreate = $modx->services->get(\GPM\Operations\Scripts\Create::class);

$keyAdd = $modx->services->get(\GPM\Operations\Key\Add::class);
$keyList = $modx->services->get(\GPM\Operations\Key\ListKeys::class);
$keyRemove = $modx->services->get(\GPM\Operations\Key\Remove::class);

$fredExportBlueprints = $modx->services->get(\GPM\Operations\Fred\Export::class);

$gpmUpdate = $modx->services->get(\GPM\Operations\GPM\Update::class);

// Commands
$application->add(new \GPM\CLI\PackageInstall($install));
$application->add(new \GPM\CLI\PackageBuild($build));
$application->add(new \GPM\CLI\PackageCreate($create));

$fredAsPackage = false;

/** @var \GPM\Model\GitPackage[] $packages */
$packages = $modx->getIterator(\GPM\Model\GitPackage::class);
foreach ($packages as $package) {
    // region: --- gpm commands
    if ($package->name === 'gpm') {
        $application->add(new \GPM\CLI\GPM\Update("{$package->dir_name}:update", $package, $gpmUpdate));
        $application->add(new \GPM\CLI\ParseSchema("{$package->dir_name}:schema", $package, $parseSchema));
        $application->add(new \GPM\CLI\Build("{$package->dir_name}:build", $package, $build));
        $application->add(new \GPM\CLI\Remove("{$package->dir_name}:remove", $package, $remove));

        $application->add(new \GPM\CLI\Migrations\Run("{$package->dir_name}:migrations:run", $package, $migrationsRun));
        continue;
    }
    // endregion

    // region: --- basic package commands
    $application->add(new \GPM\CLI\Update("{$package->dir_name}:update", $package, $update));
    $application->add(new \GPM\CLI\ParseSchema("{$package->dir_name}:schema", $package, $parseSchema));
    $application->add(new \GPM\CLI\Build("{$package->dir_name}:build", $package, $build));
    $application->add(new \GPM\CLI\Remove("{$package->dir_name}:remove", $package, $remove));
    $application->add(new \GPM\CLI\KeyAdd("{$package->dir_name}:key:add", $package, $keyAdd));
    $application->add(new \GPM\CLI\KeyList("{$package->dir_name}:key:list", $package, $keyList));
    $application->add(new \GPM\CLI\KeyRemove("{$package->dir_name}:key:remove", $package, $keyRemove));
    // endregion

    // region: --- migrations
    $application->add(new \GPM\CLI\Migrations\Run("{$package->dir_name}:migrations:run", $package, $migrationsRun));
    $application->add(new \GPM\CLI\Migrations\Create("{$package->dir_name}:migrations:create", $package, $migrationsCreate));
    // endregion

    // region: --- scripts
    $application->add(new \GPM\CLI\Scripts\Run("{$package->dir_name}:scripts:run", $package, $scriptRun));
    $application->add(new \GPM\CLI\Scripts\Create("{$package->dir_name}:scripts:create", $package, $scriptCreate));
    // endregion

    // region: --- fred
    try {
        $config = Config::load(
            $modx,
            new Stealth(),
            $modx->getOption('gpm.packages_dir') . $package->dir_name . DIRECTORY_SEPARATOR
        );
        if ($config->fred !== null) {
            $application->add(
                new \GPM\CLI\Fred\Export("{$package->dir_name}:export:fred", $package, $fredExportBlueprints)
            );
        }
    } catch (\Exception) {}
    // endregion
}


$application->run();
