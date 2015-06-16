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
    protected $modx;
    /** @var \GitPackageManagement */
    protected $gpm;
    /** @var bool */
    protected $log = true;
    /** @var string */
    protected $packagePath;
    /** @var Object\General */
    protected $general;
    /** @var Object\Action[] */
    protected $actions;
    /** @var Object\Menu[] */
    protected $menus;
    /** @var Object\Category[] */
    protected $categories;
    /** @var Object\Element\Plugin[] */
    protected $plugins;
    /** @var Object\Element\Snippet[] */
    protected $snippets;
    /** @var Object\Element\Chunk[] */
    protected $chunks;
    /** @var Object\Element\Template[] */
    protected $templates;
    /** @var Object\Element\TV[] */
    protected $tvs;
    /** @var Object\Resource[] */
    protected $resources;
    /** @var Object\Setting[] */
    protected $systemSettings;
    /** @var Object\Database */
    protected $database;
    /** @var Object\ExtensionPackage */
    protected $extensionPackage;
    /** @var Object\Build\Build */
    protected $build;
    /** @var Object\Dependency[] */
    protected $dependencies;
    /** @var Parser\Parser */
    protected $parser;

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

        $this->gpm =& $this->modx->gitpackagemanagement;
        $this->parser = new Parser\Parser($this->modx, $this);
        $this->error = new Error($this->modx);
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
        $data = $loader->{'load' . ucfirst($part)}();

        try {
            $this->parser->{'parse' . ucfirst($part)}($this->{$part}, $data, $skip);
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

        if (!is_dir($assetsFolder . $this->general->getLowCaseName())) {
            mkdir($assetsFolder . $this->general->getLowCaseName());
        }

        return $assetsFolder . $this->general->getLowCaseName() . '/';
    }

    /**
     * @return General
     */
    public function getGeneral()
    {
        return $this->general;
    }

    /**
     * @return Object\Action[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return Object\Menu[]
     */
    public function getMenus()
    {
        return $this->menus;
    }

    /**
     * @return Object\Category[]
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @return Object\Element\Plugin[]
     */
    public function getPlugins()
    {
        return $this->plugins;
    }

    /**
     * @return Object\Element\Snippet[]
     */
    public function getSnippets()
    {
        return $this->snippets;
    }

    /**
     * @return Object\Element\Chunk[]
     */
    public function getChunks()
    {
        return $this->chunks;
    }

    /**
     * @return Object\Element\Template[]
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @return Object\Element\TV[]
     */
    public function getTvs()
    {
        return $this->tvs;
    }

    /**
     * @return Object\Resource[]
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * @return Object\Setting[]
     */
    public function getSystemSettings()
    {
        return $this->systemSettings;
    }

    /**
     * @return Object\Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return Object\ExtensionPackage
     */
    public function getExtensionPackage()
    {
        return $this->extensionPackage;
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

    /**
     * @return Object\Build\Build
     */
    public function getBuild()
    {
        return $this->build;
    }

    /**
     * @return Object\Dependency[]
     */
    public function getDependencies()
    {
        return $this->dependencies;
    }

    /**
     * @return string
     */
    public function getPackagePath()
    {
        return $this->packagePath;
    }

}