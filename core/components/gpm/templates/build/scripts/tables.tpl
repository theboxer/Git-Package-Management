<?php
use xPDO\Transport\xPDOTransport;

/**
 * Create tables
 *
 * THIS SCRIPT IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general.lowCaseName}}
 * @subpackage build.scripts
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

$modx =& $transport->xpdo;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) return true;

$manager = $modx->getManager();

{foreach from=$tables item=table}
$manager->createObjectContainer({{$table}}::class);
{/foreach}

return true;
