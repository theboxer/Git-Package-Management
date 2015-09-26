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
    $modx =& $object->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('{{$lowercasename}}.core_path', null, $modx->getOption('core_path') . 'components/{{$lowercasename}}/') . 'model/';
            $modx->addPackage('{{$lowercasename}}', $modelPath, '{{$prefix}}');

            $manager = $modx->getManager();

{foreach from=$tables item=table}
            $manager->createObjectContainer('{{$table}}');
{/foreach}

            break;
    }
}

return true;