<?php
if ($object->xpdo) {
    switch ($options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION]) {
        case \xPDO\Transport\xPDOTransport::ACTION_UNINSTALL:
            /** @var modX $modx */
            $modx =& $object->xpdo;

            $modelPath = $modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/').'model/';
            $modx->addPackage('gitpackagemanagement',$modelPath);

            $manager = $modx->getManager();

            $manager->removeObjectContainer(\GitPackageManagement\Model\GitPackage::class);
            break;
    }
}
return true;
