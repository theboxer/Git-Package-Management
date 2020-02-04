<?php
/**
 * GitPackageManagement setup options
 *
 * @package gitpackagemanagement
 * @subpackage build
 */

$settings = array(
    array(
        'key' => 'packages_dir',
        'value' => '',
        'name' => 'Packages directory (absolute path)'
    ),
    array(
        'key' => 'packages_base_url',
        'value' => '/',
        'name' => 'Packages base URL'
    ),
);
switch ($options[\xPDO\Transport\xPDOTransport::PACKAGE_ACTION]) {
    case \xPDO\Transport\xPDOTransport::ACTION_INSTALL:
    case \xPDO\Transport\xPDOTransport::ACTION_UPGRADE:

        foreach ($settings as $key => $setting) {
            $settingObject = $modx->getObject(\MODX\Revolution\modSystemSetting::class, ['key' => 'gitpackagemanagement.'.$setting['key']]);
            if ($settingObject) {
                $settings[$key]['value'] = $settingObject->get('value');
            }
        }

        break;
    case \xPDO\Transport\xPDOTransport::ACTION_UNINSTALL: break;
}

$output = array();

foreach ($settings as $setting) {
    $output[] = '<label for="'. $setting['key'] .'">'. $setting['name'] .':</label><input type="text" name="'. $setting['key'] .'" id="'. $setting['key'] .'" width="300" value="'. $setting['value'] .'" />';
}


return implode('<br /><br />', $output);
