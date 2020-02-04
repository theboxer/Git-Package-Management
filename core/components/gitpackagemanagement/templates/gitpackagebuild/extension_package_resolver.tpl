<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$lowercasename}}
 * @subpackage build
 *
 * @var mixed $object
 * @var modX $modx
 * @var array $options
 */

if ($object->xpdo) {
    $modx =& $object->xpdo;
    switch ($options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION]) {
        case \xPDO\Transport\xPDOTransport::ACTION_INSTALL:
        case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('{{$lowercasename}}.core_path');

            if (empty($modelPath)) {
                $modelPath = '[[++core_path]]components/{{$lowercasename}}/model/';
            }

            if ($modx instanceof modX) {
                $modx->addExtensionPackage('{{$lowercasename}}', $modelPath, {{$extension_package_options}});
            }

            break;
        case \xPDO\Transport\xPDOTransport::ACTION_UNINSTALL:
            if ($modx instanceof modX) {
                $modx->removeExtensionPackage('{{$lowercasename}}');
            }

            break;
    }
}

return true;
