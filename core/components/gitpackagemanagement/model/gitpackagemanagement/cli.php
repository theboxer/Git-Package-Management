<?php

if (!isset($argv[1]) || !isset($argv[2])) {
    return;
}

require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/');
require_once $corePath.'model/gitpackagemanagement/gitpackagemanagement.class.php';
$modx->gitpackagemanagement = new GitPackageManagement($modx);

$modx->lexicon->load('gitpackagemanagement:default');

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
$path = $modx->getOption('processorsPath', $modx->gitpackagemanagement->config, $corePath . 'processors/') . 'cli/';

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


