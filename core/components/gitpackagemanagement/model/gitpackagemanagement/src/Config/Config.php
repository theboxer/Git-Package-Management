<?php
namespace GPM\Config;

use GPM\Config\Loader\iLoader;
use GPM\Config\Object\General;
use GPM\Error\Error;

class Config
{
    /** @var \modX */
    public $modx;
    /** @var \GitPackageManagement */
    public $gpm;
    
    /** @var string */
    public $packagePath;
    /** @var string */
    public $folderName;
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
     * @param $folderName
     * @param string|null $packagesPath
     * @throws \Exception
     */
    public function __construct(\modX &$modx, $folderName, $packagesPath = null)
    {
        $this->modx =& $modx;
        $this->init();

        if ($packagesPath === null) {
            $packagesPath = rtrim($this->gpm->getOption('packages_dir', null, null), '/');
            if ($packagesPath == null) {
                throw new \Exception($this->modx->lexicon('gitpackagemanagement.package_err_ns_packages_dir'));
            }

            $packagesPath .= '/';    
        }
        
        $packagePath = $packagesPath;

        if (empty($folderName)) {
            throw new \Exception($this->modx->lexicon('gitpackagemanagement.package_err_ns_folder_name'));
        }
        
        $this->packagePath = $packagePath . $folderName;
        $this->folderName = $folderName;
    }

    public function init()
    {
        $this->gpm =& $this->modx->gitpackagemanagement;
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

    public static function wakeMe($data, \modX &$modx)
    {
        /** @var Config $config */
        $config = unserialize($data);
        $config->modx =& $modx;
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