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
$access = $object['access'];

$templatesMap = [];

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

    $templatesMap[$template['name']] = $templateObject->get('id');
}

foreach ($access['elementCategories'] as $uuid => $accessTemplates) {
    $category = $modx->getObject(\Fred\Model\FredElementCategory::class, ['uuid' => $uuid]);
    if (!$category) continue;

    $modx->removeCollection(\Fred\Model\FredElementCategoryTemplateAccess::class, ['category' => $category->get('id')]);

    foreach ($accessTemplates as $templateName) {
        if (!isset($templatesMap[$templateName])) continue;
        $obj = $modx->newObject(\Fred\Model\FredElementCategoryTemplateAccess::class);
        $obj->set('category', $category->get('id'));
        $obj->set('template', $templatesMap[$templateName]);
        $obj->save();
    }
}

foreach ($access['blueprintCategories'] as $uuid => $accessTemplates) {
    $category = $modx->getObject(\Fred\Model\FredBlueprintCategory::class, ['uuid' => $uuid]);
    if (!$category) continue;

    $modx->removeCollection(\Fred\Model\FredBlueprintCategoryTemplateAccess::class, ['category' => $category->get('id')]);

    foreach ($accessTemplates as $templateName) {
        if (!isset($templatesMap[$templateName])) continue;
        $obj = $modx->newObject(\Fred\Model\FredBlueprintCategoryTemplateAccess::class);
        $obj->set('category', $category->get('id'));
        $obj->set('template', $templatesMap[$templateName]);
        $obj->save();
    }
}

foreach ($access['elements'] as $uuid => $accessTemplates) {
    $el = $modx->getObject(\Fred\Model\FredElement::class, ['uuid' => $uuid]);
    if (!$el) continue;

    $modx->removeCollection(\Fred\Model\FredElementTemplateAccess::class, ['element' => $el->get('id')]);

    foreach ($accessTemplates as $templateName) {
        if (!isset($templatesMap[$templateName])) continue;
        $obj = $modx->newObject(\Fred\Model\FredElementTemplateAccess::class);
        $obj->set('element', $el->get('id'));
        $obj->set('template', $templatesMap[$templateName]);
        $obj->save();
    }
}

foreach ($access['blueprints'] as $uuid => $accessTemplates) {
    $bp = $modx->getObject(\Fred\Model\FredBlueprint::class, ['uuid' => $uuid]);
    if (!$bp) continue;

    $modx->removeCollection(\Fred\Model\FredBlueprintTemplateAccess::class, ['blueprint' => $bp->get('id')]);

    foreach ($accessTemplates as $templateName) {
        if (!isset($templatesMap[$templateName])) continue;
        $obj = $modx->newObject(\Fred\Model\FredBlueprintTemplateAccess::class);
        $obj->set('blueprint', $bp->get('id'));
        $obj->set('template', $templatesMap[$templateName]);
        $obj->save();
    }
}