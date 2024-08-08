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

use Fred\Model\FredThemedTemplate;
use Fred\Model\FredBlueprint;
use Fred\Model\FredTheme;
use xPDO\Transport\xPDOTransport;
use MODX\Revolution\modSystemSetting;
use xPDO\xPDO;
use xPDO\Cache\xPDOCacheManager;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

$modx =& $transport->xpdo;

$modx->getCacheManager();
$modx->cacheManager->refresh();

$config = $modx->cacheManager->get('config', [
        xPDO::OPT_CACHE_KEY => $modx->getOption('cache_system_settings_key', null, 'system_settings'),
        xPDO::OPT_CACHE_HANDLER => $modx->getOption('cache_system_settings_handler', null, $modx->getOption(xPDO::OPT_CACHE_HANDLER)),
        xPDO::OPT_CACHE_FORMAT => (integer) $modx->getOption('cache_system_settings_format', null, $modx->getOption(xPDO::OPT_CACHE_FORMAT, null, xPDOCacheManager::CACHE_PHP)),
]);

if (empty($config)) {
    $config = $modx->cacheManager->generateConfig();
}

if (empty($config)) {
    $config = [];
    if (!$settings = $modx->getCollection(modSystemSetting::class)) {
        return;
    }
    /** @var modSystemSetting $setting */
    foreach ($settings as $setting) {
        $config[$setting->get('key')]= $setting->get('value');
    }
}

$modx->config = array_merge($modx->config, $config);
$modx->_systemConfig = $modx->config;