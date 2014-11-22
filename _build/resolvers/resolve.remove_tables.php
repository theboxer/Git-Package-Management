<?php
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_UNINSTALL:
            /** @var modX $modx */
            $modx =& $object->xpdo;

            $modelPath = $modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/').'model/';
            $modx->addPackage('gitpackagemanagement',$modelPath);

            $manager = $modx->getManager();

            $manager->removeObjectContainer('GitPackage');
            break;
    }
}
return true;