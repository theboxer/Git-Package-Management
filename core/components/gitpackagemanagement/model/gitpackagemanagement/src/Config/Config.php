<?php
namespace GPM\Config;

use GPM\Config\Loader\iLoader;
use GPM\Config\Object\General;
use GPM\Error\Error;

class Config
{
    /** @var Error */
    public $error;
    /** @var \modX */
    public $modx;
    /** @var \GitPackageManagement */
    protected $gpm;
    /** @var bool */
    protected $log = true;
    
    /** @var string */
    public $packagePath;
    /** @var Object\General */
    public $general;
    /** @var Object\Action[] */
    public $actions = [];
    /** @var Object\Menu[] */
    public $menus = [];
    /** @var Object\Category[] */
    public $categories = [];
    /** @var Object\Element\Plugin[] */
    public $plugins = [];
    /** @var Object\Element\Snippet[] */
    public $snippets = [];
    /** @var Object\Element\Chunk[] */
    public $chunks = [];
    /** @var Object\Element\Template[] */
    public $templates = [];
    /** @var Object\Element\TV[] */
    public $tvs = [];
    /** @var Object\Resource[] */
    public $resources = [];
    /** @var Object\Setting[] */
    public $systemSettings = [];
    /** @var Object\Database */
    public $database = null;
    /** @var Object\ExtensionPackage */
    public $extensionPackage = null;
    /** @var Object\Build\Build */
    public $build;
    /** @var Object\Dependency[] */
    public $dependencies = [];

    /**
     * @param \modX $modx
     * @param string $packagePath
     * @param bool $log
     */
    public function __construct(\modX &$modx, $packagePath, $log = false)
    {
        $this->modx =& $modx;
        $this->packagePath = $packagePath;
        $this->log = $log;
    }

    public function init()
    {
        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->error = new Error($this->modx);
    }

    public function __sleep()
    {
        return [
            'log', 'general', 'actions', 'menus', 
            'categories', 'plugins', 'snippets', 'chunks', 'templates', 
            'tvs', 'resources', 'systemSettings', 'database', 'extensionPackage',
            'build', 'dependencies'
        ];
    }

    public static function wakeMe($data, \modX &$modx, $packagePath)
    {
        /** @var Config $config */
        $config = unserialize($data);
        $config->modx =& $modx;
        $config->packagePath = $packagePath;
        $config->init();
        
        $config->general->setConfig($config);
        $config->build->setConfig($config);
        
        if ($config->database !== null) $config->database->setConfig($config);
        if ($config->extensionPackage !== null) $config->extensionPackage->setConfig($config);
        
        foreach ($config->actions as $action) {
            $action->setConfig($config);
        }
        
        foreach ($config->menus as $menu) {
            $menu->setConfig($config);
        }
        
        foreach ($config->categories as $category) {
            $category->setConfig($config);
        }
        
        foreach ($config->plugins as $plugin) {
            $plugin->setConfig($config);
        }
        
        foreach ($config->snippets as $snippet) {
            $snippet->setConfig($config);
        }
        
        foreach ($config->chunks as $chunk) {
            $chunk->setConfig($config);
        }
        
        foreach ($config->templates as $template) {
            $template->setConfig($config);
        }
        
        foreach ($config->tvs as $tv) {
            $tv->setConfig($config);
        }
        
        foreach ($config->resources as $resource) {
            $resource->setConfig($config);
        }
        
        foreach ($config->systemSettings as $systemSetting) {
            $systemSetting->setConfig($config);
        }
        
        foreach ($config->systemSettings as $systemSetting) {
            $systemSetting->setConfig($config);
        }
        
        foreach ($config->dependencies as $dependency) {
            $dependency->setConfig($config);
        }
        
        return $config;
    }

    public function load(iLoader $loader, $skip = true)
    {
        $this->loadPart($loader, 'general', $skip);
        $this->loadPart($loader, 'actions', $skip);
        $this->loadPart($loader, 'menus', $skip);
        $this->loadPart($loader, 'categories', $skip);
        $this->loadPart($loader, 'plugins', $skip);
        $this->loadPart($loader, 'snippets', $skip);
        $this->loadPart($loader, 'chunks', $skip);
        $this->loadPart($loader, 'templates', $skip);
        $this->loadPart($loader, 'tvs', $skip);
        $this->loadPart($loader, 'resources', $skip);
        $this->loadPart($loader, 'systemSettings', $skip);
        $this->loadPart($loader, 'database', $skip);
        $this->loadPart($loader, 'extensionPackage', $skip);
        $this->loadPart($loader, 'build', $skip);
        $this->loadPart($loader, 'dependencies', $skip);
    }

    public function loadPart(iLoader $loader, $part, $skip = true)
    {
        try {
            $loader->{'load' . ucfirst($part)}($skip);
        } catch (\Exception $e) {
            $this->error->addError($e->getMessage(), $this->log);
        }
    }

    public function checkDependencies()
    {
        $failed = [];

        foreach ($this->dependencies as $dependency) {
            $found = $this->modx->getCount('transport.modTransportPackage', ['package_name' => $dependency]);
            $foundInGPM = $this->modx->getCount('GitPackage', ['name' => $dependency, 'OR:dir_name:=' => $dependency]);

            if ($found == 0 && $foundInGPM == 0) {
                $failed[] = $dependency;
            }
        }

        if (count($failed) == 0) {
            return true;
        }

        return $failed;
    }

    public function getAssetsFolder()
    {
        $assetsFolder = $this->gpm->getOption('assetsPath');

        if (!is_dir($assetsFolder . 'packages')) {
            mkdir($assetsFolder . 'packages');
        }

        $assetsFolder .= 'packages/';

        if (!is_dir($assetsFolder . $this->general->lowCaseName)) {
            mkdir($assetsFolder . $this->general->lowCaseName);
        }

        return $assetsFolder . $this->general->lowCaseName . '/';
    }

    protected function setExtensionPackage($extensionPackage)
    {
        if (!isset($extensionPackage['serviceName']) && !isset($extensionPackage['serviceClass'])) {
            $this->extensionPackage = true;
        } else if ((!isset($extensionPackage['serviceClass']) && isset($extensionPackage['serviceClass'])) || (isset($extensionPackage['serviceClass']) && !isset($extensionPackage['serviceClass']))) {
            $this->extensionPackage = false;
        } else {
            $this->extensionPackage['serviceName'] = $extensionPackage['serviceName'];
            $this->extensionPackage['serviceClass'] = $extensionPackage['serviceClass'];
        }
    }
}