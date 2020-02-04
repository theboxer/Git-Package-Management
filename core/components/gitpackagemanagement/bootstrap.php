<?php
/**
 * @var \MODX\Revolution\modX $modx
 */

\MODX\Revolution\modX::getLoader()->addPsr4('GitPackageManagement\\', $namespace['path'] . 'src/');

$modx->services->add('gitpackagemanagement', function($c) use ($modx) {
    return new GitPackageManagement\GitPackageManagement($modx);
});
