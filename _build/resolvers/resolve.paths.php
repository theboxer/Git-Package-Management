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
    $ct = $modx->getCount('modSystemSetting',array(
        'key' => 'gitpackagemanagement.'.$key,
    ));
    if (empty($ct)) {
        $setting = $modx->newObject('modSystemSetting');
        $setting->set('key','gitpackagemanagement.'.$key);
        $setting->set('value',$value);
        $setting->set('namespace','gitpackagemanagement');
        $setting->set('area','Paths');
        $setting->save();
    }
}
if ($object->xpdo) {
    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            $modx =& $object->xpdo;

            /* create gitpackagemanagement settings */
            createSetting($modx,'packages_dir', '');
            createSetting($modx,'packages_base_url', '/');


        break;
    }
}
return true;