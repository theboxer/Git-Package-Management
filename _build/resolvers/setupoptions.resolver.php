<?php
/**
 * GitPackageManagement setup options resolver
 *
 * @package gitpackagemanagement
 * @subpackage build
 */

$success= false;
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
    case xPDOTransport::ACTION_UPGRADE:
        $settings = array(
            'packages_dir',
            'packages_base_url',
        );
        foreach ($settings as $key) {
            if (isset($options[$key])) {
                $settingObject = $object->xpdo->getObject('modSystemSetting',array('key' => 'gitpackagemanagement.'.$key));
                if ($settingObject) {
                    $settingObject->set('value',$options[$key]);
                    $settingObject->save();
                } else {
                    $object->xpdo->log(xPDO::LOG_LEVEL_ERROR,'[GitPackageManagement] gitpackagemanagement.'.$key.' setting could not be found, so the setting could not be changed.');
                }
            }
        }

        $success= true;
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        $success= true;
        break;
}
return $success;