<?php
use xPDO\Transport\xPDOTransport;

/**
 * Handles relation between template variables and templates
 *
 * THIS RESOLVER IS AUTOMATICALLY GENERATED, NO CHANGES WILL APPLY
 *
 * @package {{$general.lowCaseName}}
 * @subpackage build
 *
 * @var \MODX\Revolution\modCategory $object
 * @var \MODX\Revolution\modX $modx
 * @var array $options
 * @var array $fileMeta
 */

$modx =& $object->xpdo;
if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) return true;

$templatesCache = [];

if (isset($fileMeta['templateVars']) && is_array($fileMeta['templateVars'])) {
    foreach ($fileMeta['templateVars'] as $templateVarName => $templates) {
        /** @var \MODX\Revolution\modTemplateVar $tv */
        $tv = $modx->getObject(\MODX\Revolution\modTemplateVar::class, ['name' => $templateVarName]);
        if (!$tv) continue;

        if (empty($templates)) {
            $modx->removeCollection(\MODX\Revolution\modTemplateVarTemplate::class, ['tmplvarid' => $tv->id]);
            continue;
        }

        if (!is_array($templates)) continue;

            foreach ($templates as $templateName) {
            if (!isset($templatesCache[$templateName])) {
                /** @var \MODX\Revolution\modTemplate $template */
                $template = $modx->getObject(\MODX\Revolution\modTemplate::class, ['templatename' => $templateName]);
                if (!$template) continue;

                $templatesCache[$templateName] = $template->id;
            }

            $templateVarTemplate = $modx->getObject(\MODX\Revolution\modTemplateVarTemplate::class, ['tmplvarid' => $tv->id, 'templateid' => $templatesCache[$templateName]]);
            if ($templateVarTemplate) continue;

            $templateVarTemplate = $modx->newObject(\MODX\Revolution\modTemplateVarTemplate::class);
            $templateVarTemplate->set('tmplvarid', $tv->id);
            $templateVarTemplate->set('templateid', $templatesCache[$templateName]);
            $templateVarTemplate->save();
        }
    }
}

return true;
