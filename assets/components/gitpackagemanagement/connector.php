<?php
/**
 * GitPackageManagement Connector
 *
 * @package gitpackagemanagement
 */
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/config.core.php';
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CONNECTORS_PATH.'index.php';

$corePath = $modx->getOption('gitpackagemanagement.core_path',null,$modx->getOption('core_path').'components/gitpackagemanagement/');
require_once $corePath.'model/gitpackagemanagement/gitpackagemanagement.class.php';
$modx->gitpackagemanagement = new GitPackageManagement($modx);

$modx->lexicon->load('gitpackagemanagement:default');

/* handle request */
$path = $modx->getOption('processorsPath',$modx->gitpackagemanagement->config,$corePath.'processors/');
$modx->request->handleRequest(array(
    'processors_path' => $path,
    'location' => '',
));