<?php
/**
 * Adds modSystemSettings into package
 *
 * @package gitpackagemanagement
 * @subpackage build
 */
$settings = array();
$settings['gitpackagemanagement.enable_debug']= $modx->newObject('modSystemSetting');
$settings['gitpackagemanagement.enable_debug']->fromArray(array(
    'key' => 'gitpackagemanagement.enable_debug',
    'value' => '0',
    'xtype' => 'combo-boolean',
    'namespace' => 'gitpackagemanagement',
    'area' => 'system',
),'',true,true);

return $settings;
