<?php
/**
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general.lowCaseName}}
 * @subpackage build
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $object
 * @var array $options
 */

use Fred\Model\FredElement;
use Fred\Model\FredElementOptionSet;
use xPDO\Transport\xPDOTransport;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

$modx =& $transport->xpdo;

$map = $object['map'];
if (empty($map)) {
    return true;
}

foreach ($map as $uuid => $optionSetName) {
    /** @var FredElement $element */
    $element = $modx->getObject(FredElement::class, ['uuid' => $uuid]);
    if (!$element) continue;

    $category = $element->getOne('Category');
    if (!$category) continue;

    $optionSet = $modx->getObject(FredElementOptionSet::class, ['name' => $optionSetName, 'theme' => $category->get('theme')]);
    if (!$optionSet) continue;

    $element->set('option_set', $optionSet->id);
    $element->save();
}


