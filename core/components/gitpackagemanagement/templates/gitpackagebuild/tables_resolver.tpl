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
    $modx =& $object->xpdo;
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modelPath = $modx->getOption('{{$general->lowCaseName}}.core_path', null, $modx->getOption('core_path') . 'components/{{$general->lowCaseName}}/') . 'model/';
            $modx->addPackage('{{$general->lowCaseName}}', $modelPath, '{{$db->prefix}}');

            $manager = $modx->getManager();

{foreach from=$db->tables item=table}
            $manager->createObjectContainer('{{$table}}');
{/foreach}

            break;
    }
}

return true;