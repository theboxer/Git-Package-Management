<?php
/**
 * Resolve loading the encrypt class
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$lowercasename}}
 * @subpackage build
 *
 * @var xPDOTransport $transport
 */

if ($transport->xpdo) {
    $transport->xpdo->loadClass('transport.xPDOObjectVehicle', XPDO_CORE_PATH, true, true);
    $transport->xpdo->loadClass('encryptVehicle', MODX_CORE_PATH . 'components/{{$lowercasename}}_vehicle/', true, true);
}

return true;