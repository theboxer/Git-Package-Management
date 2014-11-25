<?php
/**
 * The base class for GitPackageManagement.
 *
 * @package gitpackagemanagement
 */
class GitPackageManagement {
    /** @var \modX $modx */
    public $modx;
    public $namespace = 'gitpackagemanagement';
    /** @var array $config */
    public $options = array();
    /** @var array $chunks */
    public $chunks = array();
    /** @var string $configPath */
    public $configPath = '/_build/config.json';

    function __construct(modX &$modx,array $options = array()) {
        $this->modx =& $modx;
        $this->namespace = $this->getOption('namespace', $options, 'gitpackagemanagement');

        $corePath = $this->getOption('core_path', $options, $this->modx->getOption('core_path', null, MODX_CORE_PATH) . 'components/gitpackagemanagement/');
        $assetsPath = $this->getOption('assets_path', $options, $this->modx->getOption('assets_path', null, MODX_ASSETS_PATH) . 'components/gitpackagemanagement/');
        $assetsUrl = $this->getOption('assets_url', $options, $this->modx->getOption('assets_url', null, MODX_ASSETS_URL) . 'components/gitpackagemanagement/');
        $connectorUrl = $assetsUrl.'connector.php';

        $this->options = array_merge(array(
            'assetsPath' => $assetsPath,
            'assetsUrl' => $assetsUrl,
            'cssUrl' => $assetsUrl . 'css/',
            'jsUrl' => $assetsUrl . 'js/',
            'imagesUrl' => $assetsUrl . 'images/',
            'connectorUrl' => $connectorUrl,
            'corePath' => $corePath,
            'modelPath' => $corePath . 'model/',
            'chunksPath' => $corePath . 'elements/chunks/',
            'snippetsPath' => $corePath . 'elements/snippets/',
            'processorsPath' => $corePath . 'processors/',
            'templatesPath' => $corePath . 'templates/',
        ),$options);

        $this->modx->addPackage('gitpackagemanagement', $this->getOption('modelPath'));
        $this->modx->lexicon->load('gitpackagemanagement:default');

    }

    /**
     * Get a local configuration option or a namespaced system setting by key.
     *
     * @param string $key The option key to search for.
     * @param array $options An array of options that override local options.
     * @param mixed $default The default value returned if the option is not found locally or as a
     * namespaced system setting; by default this value is null.
     * @return mixed The option value or the default value specified.
     */
    public function getOption($key, $options = array(), $default = null) {
        $option = $default;
        if (!empty($key) && is_string($key)) {
            if ($options != null && array_key_exists($key, $options)) {
                $option = $options[$key];
            } elseif (array_key_exists($key, $this->options)) {
                $option = $this->options[$key];
            } elseif (array_key_exists("{$this->namespace}.{$key}", $this->modx->config)) {
                $option = $this->modx->getOption("{$this->namespace}.{$key}");
            }
        }
        return $option;
    }

    public function runProcessor($action = '', $scriptProperties = array(), $location = '') {
        $path = $this->getOption('processorsPath');

        return $this->modx->runProcessor($action, $scriptProperties, array(
            'processors_path' => $path,
            'location' => $location,
        ));
    }

    public function stream_copy($src, $dest) {
        $fsrc = fopen($src,'r');
        $fdest = fopen($dest,'w+');

        $len = stream_copy_to_stream($fsrc,$fdest);

        fclose($fsrc);
        fclose($fdest);

        return $len;
    }

    public function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while(false !== ( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    $this->stream_copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function deleteDir($dirPath) {
        if (! is_dir($dirPath)) {
            return;
        }
        if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
            $dirPath .= '/';
        }
        $files = glob($dirPath . '*', GLOB_MARK);
        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->deleteDir($file);
            } else {
                unlink($file);
            }
        }
        rmdir($dirPath);
    }
}