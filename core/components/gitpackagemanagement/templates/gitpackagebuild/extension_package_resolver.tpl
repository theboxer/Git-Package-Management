<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALY GENERATED, NO CHANGES WILL APPLY
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
{if $serviceName && $serviceClass}
                $modx->addExtensionPackage('{{$lowercasename}}', $modelPath, array(
                    'serviceName' => '{{$serviceName}}',
                    'serviceClass' => '{{$serviceClass}}'
                ));
{else}
                $modx->addExtensionPackage('{{$lowercasename}}', $modelPath);
{/if}
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