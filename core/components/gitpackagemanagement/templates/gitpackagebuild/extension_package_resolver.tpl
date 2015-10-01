<?php
/**
 * Resolve creating db tables
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general->lowCaseName}}
 * @subpackage build
 */

if ($object->xpdo) {
    /** @var modX $modx */
    $modx =& $object->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('{{$general->lowCaseName}}.core_path');

            if (empty($modelPath)) {
                $modelPath = '[[++core_path]]components/{{$general->lowCaseName}}/model/';
            }

            if ($modx instanceof modX) {
{if $serviceName && $serviceClass}
                $modx->addExtensionPackage('{{$general->lowCaseName}}', $modelPath, array(
                    'serviceName' => '{{$serviceName}}',
                    'serviceClass' => '{{$serviceClass}}'
                ));
{else}
                $modx->addExtensionPackage('{{$general->lowCaseName}}', $modelPath);
{/if}
            }

            break;
        case xPDOTransport::ACTION_UNINSTALL:
            if ($modx instanceof modX) {
                $modx->removeExtensionPackage('{{$general->lowCaseName}}');
            }

            break;
    }
}
return true;