<?php

if (!isset($argv[1]) || !isset($argv[2])) {
    return;
}

require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/');

/** @var GitPackageManagement $gpm */
$gpm = $modx->getService(
    'gitpackagemanagement',
    'GitPackageManagement',
    $corePath . 'model/gitpackagemanagement/',
    array(
        'core_path' => $corePath
    )
);

$version = $modx->getVersionData();
if (version_compare($version['full_version'],'2.1.1-pl') >= 0) {
    if ($modx->user->hasSessionContext($modx->context->get('key'))) {
        $_SERVER['HTTP_MODAUTH'] = $_SESSION["modx.{$modx->context->get('key')}.user.token"];
    } else {
        $_SESSION["modx.{$modx->context->get('key')}.user.token"] = 0;
        $_SERVER['HTTP_MODAUTH'] = 0;
    }
} else {
    $_SERVER['HTTP_MODAUTH'] = $modx->site_id;
}
$_REQUEST['HTTP_MODAUTH'] = $_SERVER['HTTP_MODAUTH'];

$modx->request->loadErrorHandler();

/* handle request */
$path = $gpm->getOption('processorsPath') . 'cli/';

$action = str_replace('.', '', $argv[1]);

/** @var modProcessorResponse $response */
$response = $modx->runProcessor($action, $argv, array(
    'processors_path' => $path,
    'location' => '',
));

if (empty($response)) {
    return;
}

$response = $response->getResponse();

if ($response['success'] == false) {
    return;
}

echo $response['message'];


