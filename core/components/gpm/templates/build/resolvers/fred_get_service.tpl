<?php
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

/**
 * Loads fred
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general.lowCaseName}}
 * @subpackage build
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $options
 * @var array $fileMeta
 */

if ($options[xPDOTransport::PACKAGE_ACTION] !== xPDOTransport::ACTION_UNINSTALL) {
    /** @var modX $modx */
    $modx =& $transport->xpdo;
    $fred = $modx->services->get('fred');
}
