<?php

/**
 * Resolve paths. These are useful to change if you want to debug and/or develop
 * in a directory outside of the MODx webroot. They are not required to set
 * for basic usage.
 *
 * @package gitpackagemanagement
 * @subpackage build
 */
function createSetting(&$modx,$key,$value) {
    $ct = $modx->getCount(\MODX\Revolution\modSystemSetting::class,array(
        'key' => 'gitpackagemanagement.'.$key,
    ));
    if (empty($ct)) {
        /** @var \xPDO\Om\xPDOObject $setting */
        $setting = $modx->newObject(\MODX\Revolution\modSystemSetting::class);
        $setting->set('key','gitpackagemanagement.'.$key);
        $setting->set('value',$value);
        $setting->set('namespace','gitpackagemanagement');
        $setting->set('area','Paths');
        $setting->save();
    }
}
if ($object->xpdo) {
    switch ($options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION]) {
        case \xPDO\Transport\xPDOTransport::ACTION_INSTALL:
        case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:
            $modx =& $object->xpdo;

            /* create gitpackagemanagement settings */
            createSetting($modx,'packages_dir', '');
            createSetting($modx,'packages_base_url', '/');


        break;
    }
}
return true;
