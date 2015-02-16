<?php

require_once __DIR__ . '/../src/bootstrap.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config.core.php';
require_once MODX_CORE_PATH.'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$app = new GPM\Application();
$app->setMODX($modx);
$app->loadGPM();

$app->run();
