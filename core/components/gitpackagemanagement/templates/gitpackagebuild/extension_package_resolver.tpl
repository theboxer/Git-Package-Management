<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$lowercasename}}
 * @subpackage build
 */

if ($object->xpdo) {
    /** @var modX $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('{{$lowercasename}}.core_path');

            if (empty($modelPath)) {
                $modelPath = '[[++core_path]]components/{{$lowercasename}}/model/';
            }

            if ($modx instanceof modX) {

                $modx->addExtensionPackage('{{$lowercasename}}', $modelPath, {{$extension_package_options}});

            }

            break;
        case xPDOTransport::ACTION_UNINSTALL:
            if ($modx instanceof modX) {
                $modx->removeExtensionPackage('{{$lowercasename}}');
            }

            break;
    }
}
return true;