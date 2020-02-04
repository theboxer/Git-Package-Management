<?php
/**
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

            break;
        case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:

            break;
        case \xPDO\Transport\xPDOTransport::ACTION_UNINSTALL:

            break;
    }
}

return true;
