<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package gitpackagemanagement
 * @subpackage build
 *
 * @var mixed $object
 * @var modX $modx
 * @var array $options
 */

if ($object->xpdo) {
    $modx =& $object->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('gitpackagemanagement.core_path', null, $modx->getOption('core_path') . 'components/gitpackagemanagement/') . 'model/';
            
            $modx->addPackage('gitpackagemanagement', $modelPath, null);


            $manager = $modx->getManager();

            $manager->createObjectContainer('GitPackage');

            break;
    }
}

return true;