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
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:

            break;
        case xPDOTransport::ACTION_UPGRADE:

            break;
        case xPDOTransport::ACTION_UNINSTALL:

            break;
    }
}

return true;