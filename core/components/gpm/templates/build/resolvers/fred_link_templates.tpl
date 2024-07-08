<?php
/**
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

use Fred\Model\FredThemedTemplate;
use Fred\Model\FredBlueprint;
use Fred\Model\FredTheme;
use xPDO\Transport\xPDOTransport;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

$modx =& $transport->xpdo;

if (empty($object['templates'])) {
    return true;
}

if (empty($object['theme'])) {
    return true;
}

$templates = $object['templates'];
$theme = $object['theme'];

foreach ($templates as $template) {
    $templateObject = $modx->getObject(modTemplate::class, ['templatename' => $template['name']]);
    if (!$templateObject) continue;

    $themeObject = $modx->getObject(FredTheme::class, ['uuid' => $theme]);
    if (!$themeObject) continue;

    $blueprint = !empty($template['blueprint']) ? $modx->getObject(FredBlueprint::class, ['uuid' => $template['blueprint']]) : null;
    $blueprint = !empty($blueprint) ? $blueprint->get('id') : 0;

    $themedTemplate = $modx->newObject(FredThemedTemplate::class);
    $themedTemplate->set('template', $templateObject->get('id'));
    $themedTemplate->set('theme', $themeObject->get('id'));
    $themedTemplate->set('default_blueprint', $blueprint);
    $themedTemplate->save();
}


