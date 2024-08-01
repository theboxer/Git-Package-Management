<?php
/**
 *
 * THIS SCRIPT IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$lowCaseName}}
 * @subpackage build
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

{foreach from=$script.imports item=import}
use {{$import}};
{/foreach}

return (function () {
    {{$script.content}}
})()($transport->xpdo, $options[xPDOTransport::PACKAGE_ACTION]);