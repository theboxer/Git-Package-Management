<?php
/**
 * GitPackageManagement Connector
 *
 * @package gitpackagemanagement
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config.core.php';
require_once MODX_CORE_PATH.'config/' . MODX_CONFIG_KEY . '.inc.php';
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

/* handle request */
$modx->request->handleRequest(array(
    'processors_path' => $gpm->getOption('processorsPath'),
    'location' => '',
));