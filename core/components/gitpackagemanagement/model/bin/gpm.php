<?php

require_once dirname(dirname(__FILE__)) . '/vendor/autoload.php';

if (function_exists('ini_set')) {
    @ini_set('display_errors', 1);
    $memoryLimit = trim(ini_get('memory_limit'));
    if ($memoryLimit != -1) {
        $memoryInBytes = function ($value) {
            $unit = strtolower(substr($value, -1, 1));
            $value = (int)$value;
            switch ($unit) {
                case 'g':
                    $value *= 1024;
                case 'm':
                    $value *= 1024;
                case 'k':
                    $value *= 1024;
            }
            return $value;
        };
        // Increase memory_limit if it is lower than 512M
        if ($memoryInBytes($memoryLimit) < 512 * 1024 * 1024) {
            @ini_set('memory_limit', '512M');
        }
        unset($memoryInBytes);
    }
    unset($memoryLimit);
}

$app = new GPM\CLI\Application();

if (file_exists(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.core.php')) {
    require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.core.php';
    require_once MODX_CORE_PATH.'config/' . MODX_CONFIG_KEY . '.inc.php';
    require_once MODX_CONNECTORS_PATH . 'index.php';

    $app->setMODX($modx);
    $app->loadGPM();
}

$app->run();
