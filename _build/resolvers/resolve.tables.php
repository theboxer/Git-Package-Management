<?php
/**
 * Resolve creating db tables
 *
 * @package gitpackagemanagement
 * @subpackage build
 */
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
            $modx =& $object->xpdo;
            $modelPath = $modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/').'model/';
            $modx->addPackage('gitpackagemanagement',$modelPath);

            $manager = $modx->getManager();

            $manager->createObjectContainer('GitPackage');

            break;
        case xPDOTransport::ACTION_UPGRADE:
            break;
    }
}
return true;