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
            $modelPath = $modx->getOption('{{$lowercasename}}.core_path', null, $modx->getOption('core_path') . 'components/{{$lowercasename}}/') . 'model/';

{if !$prefix}
            $modx->addPackage('{{$lowercasename}}', $modelPath, null);
{else}
            $modx->addPackage('{{$lowercasename}}', $modelPath, '{{$prefix}}');
{/if}

{foreach from=$simpleobjects item=object}
            $modx->loadClass('{{$object}}');
{/foreach}

            $manager = $modx->getManager();

{foreach from=$tables item=table}
            $manager->createObjectContainer('{{$table}}');
{/foreach}

            break;
    }
}

return true;
