<?php

namespace GPM;

use MODX\Revolution\modX;

class GPM
{
    /** @var \MODX\Revolution\modX $modx */
    public $modx;

    public $namespace = 'gpm';

    /** @var array $config */
    public $config = [];

    function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;

        $corePath = $this->getOption('core_path', $config, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/gpm/');
        $assetsUrl = $this->getOption('assets_url', $config, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/gpm/');

        $this->config = array_merge(
            [
                'corePath'  => $corePath,
                'srcPath'   => $corePath . 'src/',
                'modelPath' => $corePath . 'src/Model/',
                'assetsUrl' => $assetsUrl,
                'cssUrl'    => $assetsUrl . 'css/',
                'jsUrl'     => $assetsUrl . 'js/',

                'templatesPath' => $corePath . 'templates/',
                'processorsPath' => $corePath . 'src/Processors',
            ],
            $config
        );
        $this->modx->lexicon->load('gpm:default');
    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param  string  $key  The option key to search for.
     * @param  array  $options  An array of options that override local options.
     * @param  mixed  $default  The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     *
     * @return mixed The option value or the default value specified.
     */
    public function getOption(string $key, $options = [], $default = null)
    {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->config)) {
                $option = $this->config[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

}
